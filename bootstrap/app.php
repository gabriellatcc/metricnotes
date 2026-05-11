<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/index.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\NormalizeApiRequestHeaders::class,
        ]);

        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->render(function (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'data' => $exception->errors(),
                'message' => 'Validação falhou.',
            ], 422);
        });

        $exceptions->render(function (AuthenticationException|UnauthorizedHttpException|TokenInvalidException|TokenExpiredException|JWTException $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Não autenticado.',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Forbidden.',
            ], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Forbidden.',
            ], 403);
        });

        $exceptions->render(function (\Throwable $exception) {
            if (config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ],
                ], 500);
            }
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Erro interno do servidor.',
            ], 500);
        });
    })->create();
