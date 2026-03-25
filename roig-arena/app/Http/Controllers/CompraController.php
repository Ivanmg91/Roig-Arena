<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Asiento;
use App\Models\Sector;
use Illuminate\Http\Request;

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
            'usuario_id' => 'required|exists:usuarios,id',
            'metodo_pago' => 'required|in:tarjeta,efectivo,transferencia'
        ]);

        $carrito = session()->get('carrito', []);
        
        if (empty($carrito)) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito está vacío'
            ], 400);
        }

        try {
            // Crear compra y procesar pago
            $total = collect($carrito)->sum('precio');
            
            // Aquí iría la lógica de crear la compra en BD
            // y marcar asientos como no disponibles
            
            session()->forget('carrito');
            
            return response()->json([
                'success' => true,
                'message' => 'Compra confirmada exitosamente',
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }
}