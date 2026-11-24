<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;

class VerificarRolEstablo
{
    public function handle(Request $request, Closure $next, string $rolRequerido = 'JEFE_ESTABLO')
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new ApiException('SIN_TOKEN', 'Token de autenticación requerido', 401);
        }

        $token = trim(substr($authHeader, 7));

        $usuarios = [
            env('API_TOKEN_JEFE_ESTABLO') => 'JEFE_ESTABLO',
            env('API_TOKEN_ELFO') => 'ELFO',
        ];

        $rolUsuario = $usuarios[$token] ?? null;

        if (!$rolUsuario) {
            throw new ApiException('TOKEN_INVALIDO', 'El token proporcionado no es válido', 401);
        }

        if ($rolUsuario !== $rolRequerido) {
            throw new ApiException(
                'ROL_INSUFICIENTE',
                "Se requiere el rol {$rolRequerido} para realizar esta acción",
                403
            );
        }

        return $next($request);
    }
}