<?php

namespace App\Http\Controllers;

use App\Services\RenoService;
use Illuminate\Http\Request;

class RenoController extends Controller
{
    public function __construct(private readonly RenoService $service) {}

    public function index(Request $request)
    {
        return response()->json($this->service->listar($request->query('estado')), 200);
    }

    public function show(string $id)
    {
        return response()->json($this->service->obtener($id), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:1'],
            'edad' => ['required', 'integer', 'between:1,25'],
            'nivelEnergia' => ['required', 'integer', 'between:0,100'],
            'estado' => ['sometimes', 'string', 'in:DISPONIBLE,EN_DESCANSO,EN_REVISION,ASIGNADO_VUELO'],
        ]);

        return response()->json($this->service->crear($validated), 201);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'min:1'],
            'edad' => ['required', 'integer', 'between:1,25'],
            'nivelEnergia' => ['required', 'integer', 'between:0,100'],
            'estado' => ['required', 'string', 'in:DISPONIBLE,EN_DESCANSO,EN_REVISION,ASIGNADO_VUELO'],
        ]);

        return response()->json($this->service->actualizar($id, $validated), 200);
    }

    public function destroy(string $id)
    {
        $this->service->eliminar($id);
        return response()->json(['mensaje' => 'Reno eliminado correctamente'], 200);
    }

    public function asignar(string $id)
    {
        return response()->json($this->service->asignarAlVuelo($id), 200);
    }
}