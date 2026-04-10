<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Asiento;
use App\Models\Sector;
use Illuminate\Http\Request;
use App\Models\Entrada;
use App\Models\EstadoAsiento;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Mostrar página de compra con datos del evento
     */
    public function show($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectoresDisponibles = $this->obtenerSectoresDisponibles($eventoId)->getData()->data;
        return view('compra.buy', compact('evento', 'sectoresDisponibles'));
    }


    public function store(Request $request)
{
        $request->validate([
            'reservas' => 'required|array',
            'reservas.*' => 'exists:estado_asientos,id',
        ]);

        $user = auth()->user();

        $reservas = EstadoAsiento::whereIn('id', $request->reservas)->get();

        foreach ($reservas as $reserva) {
            // ❌ Expirada
            if ($reserva->reservado_hasta < now()) {
                return response()->json(['error' => 'Reserva expirada'], 400);
            }

            // ❌ No pertenece al usuario
            if ($reserva->user_id !== $user->id) {
                return response()->json(['error' => 'No autorizada'], 400);
            }

            // ✅ Crear entrada
            Entrada::create([
                'user_id' => $user->id,
                'evento_id' => $reserva->evento_id,
                'asiento_id' => $reserva->asiento_id,
                'precio_pagado' => 0, // o calcular con Precio
                'codigo_qr' => Str::random(32),
            ]);

            // ✅ Marcar asiento como ocupado
            $reserva->update([
                'estado' => 'OCUPADO',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compra procesada exitosamente',
        ], 201);
    }


    /**
     * API endpoint para traer asientos por sector (JSON)
     */
    public function obtenerAsientos($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectores = $evento->sectores()->with('asientos')->get();

        return response()->json([
            'success' => true,
            'data' => $sectores
        ]);
    }

    /**
     * API endpoint filtrado por sector
     */
    public function obtenerAsientoDelSector($sectorId)
    {
        $sector = Sector::findOrFail($sectorId);
        $asientos = $sector->asientos()->where('disponible', true)->get();

        return response()->json([
            'success' => true,
            'data' => $asientos
        ]);
    }

    /**
     * Añadir asiento al carrito temporal
     */
    public function agregarAlCarrito(Request $request)
    {
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'evento_id' => 'required|exists:eventos,id'
        ]);

        $carrito = session()->get('carrito', []);
        $asientoId = $request->asiento_id;

        if (!isset($carrito[$asientoId])) {
            $asiento = Asiento::find($asientoId);
            $carrito[$asientoId] = [
                'asiento_id' => $asientoId,
                'evento_id' => $request->evento_id,
                'numero' => $asiento->numero,
                'sector' => $asiento->sector->nombre,
                'precio' => $asiento->precio
            ];
        }

        session()->put('carrito', $carrito);

        return response()->json([
            'success' => true,
            'message' => 'Asiento añadido al carrito',
            'carrito' => $carrito
        ]);
    }

    /**
     * Remover asiento del carrito
     */
    public function removerDelCarrito(Request $request)
    {
        $request->validate([
            'asiento_id' => 'required'
        ]);

        $carrito = session()->get('carrito', []);
        $asientoId = $request->asiento_id;

        if (isset($carrito[$asientoId])) {
            unset($carrito[$asientoId]);
            session()->put('carrito', $carrito);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asiento removido del carrito',
            'carrito' => $carrito
        ]);
    }

    /**
     * Obtener estado actual del carrito
     */
    public function obtenerCarrito()
    {
        $carrito = session()->get('carrito', []);
        $total = collect($carrito)->sum('precio');

        return response()->json([
            'success' => true,
            'carrito' => $carrito,
            'total' => $total,
            'cantidad' => count($carrito)
        ]);
    }

    /**
     * Obtener sectores disponibles para un evento
     */
    public function obtenerSectoresDisponibles($eventoId) {
        $evento = Evento::findOrFail($eventoId);
        $sectoresDisponibles = $evento->sectores()->activos()->get();

        return response()->json([
            'success' => true,
            'data' => $sectoresDisponibles
        ]);

    }

    /**
     * Procesar compra final
     */
    public function confirmarCompra(Request $request)
    {
        $request->validate([
            'metodo_pago' => 'required|in:tarjeta,efectivo,transferencia'
        ]);

        $user = $request->user();

        try {
            $resultado = DB::transaction(function () use ($user) {
                $reservas = EstadoAsiento::with('asiento')
                    ->where('user_id', $user->id)
                    ->where('estado', 'RESERVADO')
                    ->where('reservado_hasta', '>', now())
                    ->lockForUpdate()
                    ->get();

                if ($reservas->isEmpty()) {
                    return null;
                }

                $total = 0;

                foreach ($reservas as $reserva) {
                    $precioAsiento = (float) ($reserva->asiento->precio ?? 0);
                    $total += $precioAsiento;

                    Entrada::create([
                        'user_id' => $user->id,
                        'evento_id' => $reserva->evento_id,
                        'asiento_id' => $reserva->asiento_id,
                        'precio_pagado' => $precioAsiento,
                        'codigo_qr' => Str::random(32),
                    ]);

                    $reserva->update([
                        'estado' => 'OCUPADO',
                    ]);
                }

                return [
                    'total' => $total,
                    'cantidad_entradas' => $reservas->count(),
                ];
            });

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Compra confirmada exitosamente',
                'total' => $resultado['total'],
                'cantidad_entradas' => $resultado['cantidad_entradas']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }
}
