<?php

namespace App\Http\Controllers;

use App\Services\MantenimientoService;
use Illuminate\Http\Request;

class MantenimientoController extends Controller
{
    public function __construct(private readonly MantenimientoService $service) {}

    public function index(Request $request)
    {
        return response()->json($this->service->listar($request->query('zona')), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zona' => ['required', 'string', 'in:PISTA_DESPEGUE,ESTABLO,BODEGA_REGALOS,HANGAR_TRINEO'],
            'tipo' => ['required', 'string', 'in:LIMPIEZA_NIEVE,REPARACION,INSPECCION'],
            'fechaProgramada' => ['required', 'date', 'after_or_equal:today'],
            'responsable' => ['required', 'string', 'min:1'],
        ]);

        $resultado = $this->service->programar($validated);
        $payload = $resultado['data'];
        if ($resultado['mensaje']) {
            $payload['confirmacion'] = $resultado['mensaje'];
        }

        return response()->json($payload, $resultado['status']);
    }

    public function cambiarEstado(Request $request, string $id)
    {
        $validated = $request->validate([
            'estado' => ['required', 'string', 'in:PROGRAMADO,EN_CURSO,COMPLETADO,CANCELADO,PENDIENTE_CLIMA'],
        ]);

        return response()->json($this->service->cambiarEstado($id, $validated['estado']), 200);
    }
}