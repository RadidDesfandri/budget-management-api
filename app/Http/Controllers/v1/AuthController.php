<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Mail\UserRegisteredMail;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $user = $this->userRepository->create($validatedData);

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify', // Nama route API verify
                Carbon::now()->addMinutes(60),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );

            $parsedUrl = parse_url($verificationUrl);
            $queryString = $parsedUrl['query'];

            $frontendUrl = config('app.frontend_url') . '/verify-email/'
                . $user->getKey() . '/'
                . sha1($user->getEmailForVerification()) . '?'
                . $queryString;

            Mail::to($user->email)->send(new UserRegisteredMail($user, $frontendUrl));

            DB::commit();

            return $this->successResponse('User registered successfully. Please check your email.', $user, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse('Registration failed. Please try again later.', $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ], [
            'email.exists' => 'The provided email is not registered.',
        ]);

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        $user = Auth::user();

        if (!$user->email_verified_at) {
            return $this->errorResponse('Email not verified', null, 401);
        }

        $data = [
            'user' => $user,
            'token' => $token
        ];

        return $this->successResponse('User logged in successfully', $data, 200);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return $this->successResponse('User logged out successfully', null, 200);
    }

    public function me(Request $request)
    {
        $user = $this->userRepository->find($request->user()->id);

        return $this->successResponse('User retrieved successfully', $user, 200);
    }

    public function verifyEmail(Request $request)
    {
        $user = $this->userRepository->findOrFail($request->id);

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->errorResponse('Invalid verification link', null, 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified', null, 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return $this->successResponse('Email verified successfully', null, 200);
    }

    public function sendResetPasswordEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'The provided email is not registered.',
        ]);

        // Key untuk Rate Limiter
        $throttleKey = 'send-reset-password:' . $request->email;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->errorResponse('Too many requests. Please try again in ' . $seconds . ' seconds.', null, 429);
        }

        $user = $this->userRepository->findByEmail($request->email);

        $resetUrl = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(15),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );

        $parsedUrl = parse_url($resetUrl);

        $frontendUrl = config('app.frontend_url') . '/reset-password/'
            . $user->id . '/'
            . sha1($user->email) . '?'
            . $parsedUrl['query'];

        // Hitung percobaan (Hit) jika email berhasil terkirim
        // Parameter kedua adalah waktu expire dalam detik (86400 detik = 24 jam)
        Mail::to($user->email)->send(new ForgotPasswordMail($user, $frontendUrl));
        RateLimiter::hit($throttleKey, 86400);

        return $this->successResponse('Password reset email sent successfully', null, 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user = $this->userRepository->findOrFail($request->id);

        if (!hash_equals((string) $request->route('hash'), sha1($user->email))) {
            return $this->errorResponse('Invalid reset link', null, 403);
        }

        $resetLimitKey = 'password-changed:' . $user->id;
        if (RateLimiter::tooManyAttempts($resetLimitKey, 1)) {
            return $this->errorResponse('You can only reset your password once per week.', null, 429);
        }

        if ($user->password_changed_at && $user->password_changed_at->addWeek() > now()) {
            return $this->errorResponse('You can only reset your password once per week.', null, 429);
        }

        if (Hash::check($request->password, $user->password)) {
            return $this->errorResponse('New password cannot be the same as the old password.', [
                'password' => 'New password cannot be the same as the old password.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->password_changed_at = now();
        $user->save();

        RateLimiter::hit($resetLimitKey, 604800);

        // Clear Rate Limiter setelah password berhasil direset
        RateLimiter::clear('send-reset-password:' . $user->email);

        return $this->successResponse('Password reset successfully', null, 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6',
        ]);

        $user = $request->user();

        $changeLimiterKey = 'password-changed:' . $user->id;
        if (RateLimiter::tooManyAttempts($changeLimiterKey, 1)) {
            return $this->errorResponse('You can only change your password once per week.', null, 429);
        }

        if ($user->password_changed_at && $user->password_changed_at->addWeek() > now()) {
            return $this->errorResponse('You can only change your password once per week.', null, 429);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect.', [
                'current_password' => 'Current password is incorrect.',
            ], 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return $this->errorResponse('New password cannot be the same as the old password.', [
                'new_password' => 'New password cannot be the same as the old password.',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        RateLimiter::hit($changeLimiterKey, 604800);

        return $this->successResponse('Password changed successfully', null, 200);
    }

    public function editProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $user->name = $request->name;
        $user->save();

        return $this->successResponse('Profile updated successfully', $user, 200);
    }

    public function changeAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $path = $request->file('avatar')->store('avatars', 'public');

            $user->avatar_url = $path;
        }

        $user->save();

        return $this->successResponse('Avatar updated successfully', $user, 200);
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
            Storage::disk('public')->delete($user->avatar_url);
            $user->avatar_url = null;
            $user->save();
            return $this->successResponse('Avatar removed successfully', $user, 200);
        }

        return $this->errorResponse('No profile picture to delete', null, 404);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $user->delete();

        return $this->successResponse('Account deleted successfully', null, 200);
    }
}
