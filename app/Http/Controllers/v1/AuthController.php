<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Mail\UserRegisteredMail;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

            $frontendUrl = config('app.frontend_url') . '/verify-email/' . $user->getKey() . '/' . sha1($user->getEmailForVerification()) . '?' . $queryString;

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
            'email' => 'required|email',
            'password' => 'required|string|min:6',
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
        $user = $request->user();

        return $this->successResponse('User retrieved successfully', $user, 200);
    }

    public function verifyEmail(Request $request)
    {
        $user = $this->userRepository->findOrFail($request->id);

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->errorResponse('Invalid verification link', null, 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified', null, 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return $this->successResponse('Email verified successfully', null, 200);
    }
}
