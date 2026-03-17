<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsientoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SectorController;

// ============================================
// RUTAS PUBLICAS
// ============================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/eventos', [EventoController::class, 'index']);
Route::get('/eventos/{id}', [EventoController::class, 'show']);
Route::get('/eventos/{eventoId}/asientos', [AsientoController::class, 'porEvento']);
Route::get('/eventos/{eventoId}/sectores/{sectorId}/asientos', [AsientoController::class, 'porSector']);
Route::get('/sectores', [SectorController::class, 'index']);

// ============================================
// RUTAS PROTEGIDAS (USUARIO AUTENTICADO)
// ============================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/reservas', [ReservaController::class, 'store']);
    Route::get('/reservas', [ReservaController::class, 'index']);
    Route::delete('/reservas/{id}', [ReservaController::class, 'destroy']);

    Route::post('/compras', [CompraController::class, 'store']);

    Route::get('/entradas', [EntradaController::class, 'index']);
    Route::get('/entradas/{id}', [EntradaController::class, 'show']);
});

// ============================================
// RUTAS SOLO ADMINISTRADOR
// ============================================
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/eventos', [EventoController::class, 'store']);
    Route::put('/eventos/{id}', [EventoController::class, 'update']);
    Route::delete('/eventos/{id}', [EventoController::class, 'destroy']);

    Route::post('/sectores', [SectorController::class, 'store']);
    Route::put('/sectores/{id}', [SectorController::class, 'update']);
    Route::delete('/sectores/{id}', [SectorController::class, 'destroy']);
});