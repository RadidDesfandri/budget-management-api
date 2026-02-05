<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data'    => null,
                    'error'   => 'Unauthenticated or token missing.',
                    'statusCode' => 401,
                ], 401);
            }
        });

        // Handle Token Expired (JWT)
        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token Expired',
                'data'    => null,
                'error'   => 'Your token has expired, please login again.',
                'statusCode' => 401,
            ], 401);
        });

        // Handle Token Invalid (JWT)
        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Token Invalid',
                'data'    => null,
                'error'   => 'The token provided is invalid.',
                'statusCode' => 401,
            ], 401);
        });

        // Handle JWT Generic Exception
        $exceptions->render(function (JWTException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'JWT Error',
                'data'    => null,
                'error'   => 'Could not parse token.',
                'statusCode' => 401,
            ], 401);
        });

        // Rate Limiting (429) - Terlalu banyak request
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too Many Requests',
                    'data'    => null,
                    'error'   => 'You have exceeded the rate limit.',
                    'statusCode' => 429,
                ], 429);
            }
        });

        // Model Not Found (404) - findOrFail gagal
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource Not Found',
                    'data'    => null,
                    'error'   => 'The requested resource (ID) was not found.',
                    'statusCode' => 404,
                ], 404);
            }
        });

        // Handle Route Not Found (404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route not found',
                    'data'    => null,
                    'error'   => 'Endpoint not found.',
                    'statusCode' => 404,
                ], 404);
            }
        });

        // Handle Method Not Allowed (405)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method Not Allowed',
                    'data'    => null,
                    'error'   => $e->getMessage(),
                    'statusCode' => 405,
                ], 405);
            }
        });

        // Handle Validation
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => null,
                    'errors' => $e->errors(),
                    'statusCode' => 422,
                ], 422);
            }
        });

        // Handle Internal Server Error
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $errorMessage = app()->isProduction()
                    ? 'Something went wrong on the server.'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => 'Internal Server Error',
                    'data' => null,
                    'error' => $errorMessage,
                    'statusCode' => 500,
                ], 500);
            }
        });
    })->create();
