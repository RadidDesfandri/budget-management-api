<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        // Handle Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route not found.',
                    'data'    => null,
                    'error'   => 'Resource or endpoint not found',
                    'statusCode' => 404,
                ], 404);
            }
        });

        // Handle Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed.',
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
                return response()->json([
                    'success' => false,
                    'message' => 'Internal Server Error',
                    'data' => null,
                    'error' => $e->getMessage(),
                    'statusCode' => 500,
                ], 500);
            }
        });
    })->create();
