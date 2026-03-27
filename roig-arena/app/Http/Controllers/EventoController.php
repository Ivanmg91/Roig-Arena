<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    /**
     * Listar eventos futuros (público)
     */
    public function index()
    {
        $eventos = Evento::futuros()
            ->with(['precios.sector'])
            ->get();

        return response()->json([
            'data' => $eventos,
        ]);
    }

    /**
     * Ver detalle de un evento (público)
     */
    public function show($id)
    {
        $evento = Evento::with(['precios.sector'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'asientos_disponibles' => $evento->totalAsientosDisponibles(),
                'entradas_vendidas' => $evento->totalEntradasVendidas(),
                'evento' => $evento,
                'sectores_disponibles' => $evento->sectoresDisponibles(),
            ],
        ]);
    }

    /**
     * Crear evento (admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion_corta' => 'required|string|max:255',
            'descripcion_larga' => 'required|string',
            'poster_url' => 'nullable|url',
            'poster_ancho_url' => 'nullable|url',
            'fecha' => 'required|date|unique:eventos,fecha',
            'hora' => 'required|date_format:H:i',
        ]);

        $evento = Evento::create($request->all());

        return response()->json([
            'data' => $evento,
            'message' => 'Evento creado correctamente',
        ], 201);
    }

    /**
     * Actualizar evento (admin)
     */
    public function update(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion_corta' => 'sometimes|string|max:255',
            'descripcion_larga' => 'sometimes|string',
            'poster_url' => 'nullable|url',
            'poster_ancho_url' => 'nullable|url',
            'fecha' => 'sometimes|date|unique:eventos,fecha,' . $id,
            'hora' => 'sometimes|date_format:H:i',
        ]);

        $evento->update($request->all());

        return response()->json([
            'data' => $evento,
            'message' => 'Evento actualizado correctamente',
        ]);
    }

    /**
     * Eliminar evento (admin)
     */
    public function destroy($id)
    {
        $evento = Evento::findOrFail($id);
        
        // Verificar que no tenga entradas vendidas
        if ($evento->totalEntradasVendidas() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar un evento con entradas vendidas',
            ], 400);
        }

        $evento->delete();

        return response()->json([
            'message' => 'Evento eliminado correctamente',
        ]);
    }
}