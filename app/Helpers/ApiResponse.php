<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function respond(
        bool $success,
        mixed $data = null,
        string $message = '',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    protected function respondSuccess(
        mixed $data = null,
        string $message = 'Operation completed successfully.',
        int $statusCode = 200
    ): JsonResponse {
        return $this->respond(true, $data, $message, $statusCode);
    }

    protected function respondError(
        string $message = 'An unexpected error occurred.',
        mixed $data = null,
        int $statusCode = 400
    ): JsonResponse {
        return $this->respond(false, $data, $message, $statusCode);
    }
}
