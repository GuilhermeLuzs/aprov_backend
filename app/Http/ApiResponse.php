<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Status;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = '', int $status = Status::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, array $errors = [], int $status = Status::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
