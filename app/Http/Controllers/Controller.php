<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Status;

abstract class Controller
{
    protected function sendResponse(mixed $data = null, string $message = '', int $status = Status::HTTP_OK): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function sendError(string $message, array $errors = [], int $status = Status::HTTP_BAD_REQUEST): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }
}
