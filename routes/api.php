<?php

use App\Http\Controllers\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'throttle:api'], function () {
    // Test Rate Limiting
    Route::get('test', function () {
        return response()->json(['message' => 'API Works!']);
    });

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'sendResetPasswordEmail']);
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware(['signed']);
    Route::post('reset-password/{id}/{hash}', [AuthController::class, 'resetPassword'])
        ->name('password.reset')
        ->middleware(['signed']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::put('edit-profile', [AuthController::class, 'editProfile']);
        Route::put('change-avatar', [AuthController::class, 'changeAvatar']);
        Route::delete('remove-avatar', [AuthController::class, 'removeAvatar']);
        Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
