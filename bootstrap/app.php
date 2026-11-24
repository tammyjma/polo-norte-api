<?php

use App\Exceptions\ApiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'rol.establo' => \App\Http\Middleware\VerificarRolEstablo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $responder = function (string $codigo, string $mensaje, int $status, Request $request, array $detalles = []) {
            return response()->json([
                'codigo' => $codigo,
                'mensaje' => $mensaje,
                'detalles' => $detalles,
                'timestamp' => now()->toIso8601ZuluString(),
                'ruta' => '/' . ltrim($request->path(), '/'),
            ], $status);
        };

        $exceptions->render(function (ApiException $e, Request $request) use ($responder) {
            return $responder($e->codigo, $e->getMessage(), $e->statusCode, $request, $e->detalles);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($responder) {
            return $responder('FORMATO_INVALIDO', 'Los datos enviados no son válidos', 400, $request, $e->errors());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($responder) {
            return $responder('NO_EXISTE', 'El recurso o ruta solicitada no existe', 404, $request);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($responder) {
            if ($request->is('api/*')) {
                return $responder('ERROR_INTERNO', 'Error inesperado en el servidor: ' . $e->getMessage(), 500, $request);
            }
            return null;
        });
    })->create();