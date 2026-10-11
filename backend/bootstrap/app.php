<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Semua error pada /api/* dijawab dalam bentuk JSON.
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Belum login atau token tidak valid/kedaluwarsa.',
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Data yang dikirim tidak valid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
                ], 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Data atau alamat yang diminta tidak ditemukan.',
                ], 404);
            }
        });

        // Cadangan terakhir. Saat produksi (APP_DEBUG=false) detail error tidak dibocorkan ke klien.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && !config('app.debug')) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                return response()->json([
                    'sukses' => false,
                    'pesan' => $status === 500
                        ? 'Terjadi kesalahan pada server.'
                        : ($e->getMessage() ?: 'Permintaan tidak dapat diproses.'),
                ], $status);
            }
        });
    })->create();