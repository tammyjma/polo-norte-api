<?php

use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\RenoController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de renos
Route::get('/renos', [RenoController::class, 'index']);
Route::get('/renos/{id}', [RenoController::class, 'show']);

// Rutas protegidas para rol JEFE_ESTABLO
Route::middleware('rol.establo:JEFE_ESTABLO')->group(function () {
    Route::post('/renos', [RenoController::class, 'store']);
    Route::put('/renos/{id}', [RenoController::class, 'update']);
    Route::delete('/renos/{id}', [RenoController::class, 'destroy']);
    Route::patch('/renos/{id}/asignacion', [RenoController::class, 'asignar']);
});

// Rutas públicas de mantenimientos
Route::post('/mantenimientos', [MantenimientoController::class, 'store']);
Route::get('/mantenimientos', [MantenimientoController::class, 'index']);
Route::patch('/mantenimientos/{id}/estado', [MantenimientoController::class, 'cambiarEstado']);