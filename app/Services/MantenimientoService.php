<?php

namespace App\Services;

use App\Adapters\MeteoAdapter;
use App\Exceptions\ApiException;
use App\Repositories\EstabloRepository;

class MantenimientoService
{
    private const TRANSICIONES = [
        'PROGRAMADO' => ['EN_CURSO', 'CANCELADO'],
        'EN_CURSO' => ['COMPLETADO'],
        'COMPLETADO' => [],
        'CANCELADO' => [],
        'PENDIENTE_CLIMA' => [],
    ];

    public function __construct(
        private readonly EstabloRepository $repo,
        private readonly MeteoAdapter $meteoAdapter
    ) {}

    public function listar(?string $zona): array
    {
        return $this->repo->listarMantenimientos($zona);
    }

    public function programar(array $data): array
    {
        $estado = 'PROGRAMADO';
        $httpStatus = 201;
        $mensajeConfirmacion = null;

        // Regla 4 y Requisito 5: consultar clima en PISTA_DESPEGUE
        if ($data['zona'] === 'PISTA_DESPEGUE') {
            try {
                $pronostico = $this->meteoAdapter->obtenerPronostico($data['zona'], $data['fechaProgramada']);

                if ($pronostico === 'VENTISCA') {
                    throw new ApiException(
                        'PRONOSTICO_VENTISCA',
                        'No se puede programar mantenimiento en PISTA_DESPEGUE cuando el pronóstico es VENTISCA',
                        422
                    );
                }
            } catch (ApiException $e) {
                if ($e->statusCode === 422) {
                    throw $e;
                }

                // Fault o timeout (> 3s): guardar como PENDIENTE_CLIMA y responder 202
                $estado = 'PENDIENTE_CLIMA';
                $httpStatus = 202;
                $mensajeConfirmacion = 'Mantenimiento guardado como PENDIENTE_CLIMA; se confirmará cuando vuelva el servicio meteorológico';
            }
        }

        $mantenimiento = $this->repo->guardarMantenimiento([
            'zona' => $data['zona'],
            'tipo' => $data['tipo'],
            'fechaProgramada' => $data['fechaProgramada'],
            'responsable' => $data['responsable'],
            'estado' => $estado,
        ]);

        return [
            'data' => $mantenimiento,
            'status' => $httpStatus,
            'mensaje' => $mensajeConfirmacion,
        ];
    }

    public function cambiarEstado(string $id, string $nuevoEstado): array
    {
        $mant = $this->repo->buscarMantenimiento($id);
        if (!$mant) {
            throw new ApiException('MANTENIMIENTO_NO_EXISTE', "No existe un mantenimiento con id {$id}", 404);
        }

        $estadoActual = $mant['estado'];
        $permitidos = self::TRANSICIONES[$estadoActual] ?? [];

        // Regla 3: validar transición de estado
        if (!in_array($nuevoEstado, $permitidos, true)) {
            throw new ApiException(
                'TRANSICION_INVALIDA',
                "No se puede pasar de {$estadoActual} a {$nuevoEstado}",
                409
            );
        }

        $mant['estado'] = $nuevoEstado;
        return $this->repo->guardarMantenimiento($mant, $id);
    }
}