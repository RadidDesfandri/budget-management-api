<?php

use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\InvitationController;
use App\Http\Controllers\v1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::group(["middleware" => "throttle:api"], function () {
    // Test Rate Limiting
    Route::get("test", function () {
        return response()->json(["message" => "API Works!"]);
    });

    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"])->name("login");
    Route::post("forgot-password", [
        AuthController::class,
        "sendResetPasswordEmail",
    ]);
    Route::get("email/verify/{id}/{hash}", [
        AuthController::class,
        "verifyEmail",
    ])
        ->name("verification.verify")
        ->middleware(["signed"]);
    Route::post("reset-password/{id}/{hash}", [
        AuthController::class,
        "resetPassword",
    ])
        ->name("password.reset")
        ->middleware(["signed"]);

    Route::get("org/invitation/verify", [
        InvitationController::class,
        "verifyTokenInvitation",
    ]);

    Route::group(["middleware" => "auth:api"], function () {
        Route::post("logout", [AuthController::class, "logout"]);
        Route::post("change-password", [
            AuthController::class,
            "changePassword",
        ]);
        Route::put("edit-profile", [AuthController::class, "editProfile"]);
        Route::put("change-avatar", [AuthController::class, "changeAvatar"]);
        Route::delete("remove-avatar", [AuthController::class, "removeAvatar"]);
        Route::delete("delete-account", [
            AuthController::class,
            "deleteAccount",
        ]);
        Route::get("me", [AuthController::class, "me"]);

        Route::prefix("org")->group(function () {
            Route::post("store", [OrganizationController::class, "store"]);

            Route::prefix("{organization_id}")
                ->middleware(["org.access"])
                ->group(function () {
                    Route::get("/", [OrganizationController::class, "show"]);

                    Route::get("dropdown", [
                        OrganizationController::class,
                        "orgDropdownOptions",
                    ]);
                    Route::get("member-list", [
                        OrganizationController::class,
                        "memberList",
                    ]);

                    Route::middleware(["org.role:owner,admin"])->group(
                        function () {
                            Route::post("invitation/create", [
                                InvitationController::class,
                                "createInvitation",
                            ]);

                            Route::put("member/{user_id}/change-role", [
                                OrganizationController::class,
                                "changeRole",
                            ]);

                            Route::delete("member/{user_id}", [
                                OrganizationController::class,
                                "deleteMember",
                            ]);
                        },
                    );
                });
        });

        Route::prefix("org/invitation")->group(function () {
            Route::post("accept", [
                InvitationController::class,
                "acceptInvitation",
            ]);
            Route::post("reject", [
                InvitationController::class,
                "rejectInvitation",
            ]);
            Route::get("list", [InvitationController::class, "getInvitations"]);
        });
    });
});
