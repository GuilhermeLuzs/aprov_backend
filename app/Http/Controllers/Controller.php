<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response as Status;

abstract class Controller
{
    protected function sendResponse(mixed $data = null, string $message = '', int $status = Status::HTTP_OK): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function viewer(Request $request): ?User
    {
        return $request->user('sanctum');
    }

    protected function viewerCustomer(Request $request): ?Customer
    {
        return $this->viewer($request)?->customer;
    }

    protected function sendPage(LengthAwarePaginator $page, string $key, mixed $items): JsonResponse
    {
        return $this->sendResponse([
            'page' => $page->currentPage(),
            'pageCount' => max(1, $page->lastPage()),
            'total' => $page->total(),
            $key => $items,
        ]);
    }

    protected function sendError(string $message, array $errors = [], int $status = Status::HTTP_BAD_REQUEST): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }
}
