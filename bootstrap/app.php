<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'soap/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 401 Unauthorized (RFC 7235)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'type'     => 'https://tools.ietf.org/html/rfc7235#section-3.1',
                    'title'    => 'Unauthorized',
                    'status'   => 401,
                    'detail'   => $e->getMessage() ?: 'Unauthenticated. Token otentikasi tidak valid atau tidak disertakan.',
                    'instance' => $request->getRequestUri(),
                ], 401, ['Content-Type' => 'application/problem+json']);
            }
        });

        // 404 Not Found (RFC 7231)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $detail = $e->getMessage() ?: 'Resource yang diminta tidak ditemukan.';
                if ($e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $model = class_basename($e->getPrevious()->getModel());
                    $detail = "Resource {$model} yang diminta tidak ditemukan.";
                }

                return response()->json([
                    'type'     => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                    'title'    => 'Not Found',
                    'status'   => 404,
                    'detail'   => $detail,
                    'instance' => $request->getRequestUri(),
                ], 404, ['Content-Type' => 'application/problem+json']);
            }
        });

        // 422 Unprocessable Content (RFC 4918)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'type'     => 'https://tools.ietf.org/html/rfc4918#section-11.2',
                    'title'    => 'Unprocessable Content',
                    'status'   => 422,
                    'detail'   => $e->getMessage() ?: 'Data yang dikirimkan tidak valid.',
                    'instance' => $request->getRequestUri(),
                    'errors'   => $e->errors(),
                ], 422, ['Content-Type' => 'application/problem+json']);
            }
        });

        // HttpExceptionInterface umum (400, 403, 409, 502, dsb.)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $e->getStatusCode();
                $titles = [
                    400 => 'Bad Request',
                    403 => 'Forbidden',
                    409 => 'Conflict',
                    500 => 'Internal Server Error',
                    502 => 'Bad Gateway',
                ];
                $title = $titles[$status] ?? 'HTTP Error';

                return response()->json([
                    'type'     => "https://tools.ietf.org/html/rfc7231#section-6.5",
                    'title'    => $title,
                    'status'   => $status,
                    'detail'   => $e->getMessage() ?: $title,
                    'instance' => $request->getRequestUri(),
                ], $status, ['Content-Type' => 'application/problem+json']);
            }
        });
    })->create();
