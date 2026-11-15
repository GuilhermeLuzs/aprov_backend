<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        if (! $role instanceof UserRole || ! in_array($role->value, $roles, true)) {
            return ApiResponse::error('Você não tem acesso a isso.', [], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
