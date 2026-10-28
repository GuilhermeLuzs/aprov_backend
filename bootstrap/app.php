<?php

use App\Http\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$httpMessages = [
    403 => 'Você não tem acesso a isso.',
    404 => 'Não encontrado.',
    405 => 'Esta rota não aceita esse método.',
    429 => 'Muitas tentativas. Tente de novo daqui a pouco.',
];

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) use ($httpMessages): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error($exception->validator->errors()->first(), $exception->errors(), $exception->status);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Faça login para continuar.', [], 401);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($httpMessages) {
            if ($request->expectsJson()) {
                $status = $exception->getStatusCode();

                return ApiResponse::error($httpMessages[$status] ?? 'Não foi possível concluir a chamada.', [], $status);
            }
        });
    })->create();
