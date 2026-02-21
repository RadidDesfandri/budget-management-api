<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Format response sukses
     */
    public function successResponse(
        $message = "Success",
        $data,
        $statusCode = 200,
    ) {
        return response()->json(
            [
                "success" => true,
                "message" => $message,
                "data" => $data,
                "error" => null,
                "statusCode" => $statusCode,
            ],
            $statusCode,
        );
    }

    /**
     * Format response error
     */
    public function errorResponse($message, $error = null, $statusCode = 400)
    {
        return response()->json(
            [
                "success" => false,
                "message" => $message,
                "data" => null,
                "error" => $error,
                "statusCode" => $statusCode,
            ],
            $statusCode,
        );
    }
}
