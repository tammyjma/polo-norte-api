<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\EstabloRepository;

class RenoService
{
    public function __construct(private readonly EstabloRepository $repo) {}

    public function listar(?string $estado): array
    {
        return $this->repo->listarRenos($estado);
    }

    public function obtener(string $id): array
    {
        $reno = $this->repo->buscarReno($id);
        if (!$reno) {
            throw new ApiException('RENO_NO_EXISTE', "No existe un reno con id {$id}", 404);
        }
        return $reno;
    }

    public function crear(array $data): array
    {
        if ($this->repo->existeNombreReno($data['nombre'])) {
            throw new ApiException('NOMBRE_DUPLICADO', 'Ya existe un reno registrado con ese nombre', 422);
        }

        return $this->repo->guardarReno([
            'nombre' => $data['nombre'],
            'edad' => (int) $data['edad'],
            'nivelEnergia' => (int) $data['nivelEnergia'],
            'estado' => $data['estado'] ?? 'DISPONIBLE',
        ]);
    }

    public function actualizar(string $id, array $data): array
    {
        $this->obtener($id);

        if ($this->repo->existeNombreReno($data['nombre'], $id)) {
            throw new ApiException('NOMBRE_DUPLICADO', 'Ya existe otro reno con ese nombre', 422);
        }

        return $this->repo->guardarReno([
            'nombre' => $data['nombre'],
            'edad' => (int) $data['edad'],
            'nivelEnergia' => (int) $data['nivelEnergia'],
            'estado' => $data['estado'],
        ], $id);
    }

    public function eliminar(string $id): void
    {
        $reno = $this->obtener($id);

        // Regla 5: no se puede eliminar un reno en estado ASIGNADO_VUELO
        if ($reno['estado'] === 'ASIGNADO_VUELO') {
            throw new ApiException(
                'RENO_ASIGNADO_VUELO',
                'No se puede eliminar un reno en estado ASIGNADO_VUELO',
                409
            );
        }

        $this->repo->eliminarReno($id);
    }

    public function asignarAlVuelo(string $id): array
    {
        $reno = $this->obtener($id);

        // Regla 1: nivelEnergia >= 70 y estado DISPONIBLE
        if ($reno['estado'] !== 'DISPONIBLE' || (int) $reno['nivelEnergia'] < 70) {
            throw new ApiException(
                'RENO_NO_APTO_VUELO',
                'El reno debe estar DISPONIBLE y tener nivelEnergia mayor o igual a 70 para asignarse al vuelo',
                409
            );
        }

        // Regla 2: máximo 9 renos asignados al vuelo
        $asignados = array_filter(
            $this->repo->listarRenos(),
            fn($r) => $r['estado'] === 'ASIGNADO_VUELO' && $r['id'] !== $id
        );

        if (count($asignados) >= 9) {
            throw new ApiException(
                'LIMITE_RENOS_VUELO',
                'Ya hay 9 renos asignados al vuelo; no se puede asignar un décimo reno',
                409
            );
        }

        $reno['estado'] = 'ASIGNADO_VUELO';
        return $this->repo->guardarReno($reno, $id);
    }
}