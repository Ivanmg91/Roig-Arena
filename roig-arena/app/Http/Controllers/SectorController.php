<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    /**
     * Listar sectores activos (público)
     */
    public function index()
    {
        $sectores = Sector::activos()
            ->withCount('asientos')
            ->get();

        return response()->json([
            'data' => $sectores,
        ]);
    }

    /**
     * Crear sector (admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:sectores',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $sector = Sector::create($request->all());

        return response()->json([
            'data' => $sector,
            'message' => 'Sector creado correctamente',
        ], 201);
    }

    /**
     * Actualizar sector (admin)
     */
    public function update(Request $request, $id)
    {
        $sector = Sector::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:sectores,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $sector->update($request->all());

        return response()->json([
            'data' => $sector,
            'message' => 'Sector actualizado correctamente',
        ]);
    }

    /**
     * Eliminar sector (admin)
     */
    public function destroy($id)
    {
        $sector = Sector::findOrFail($id);
        
        // Verificar que no tenga asientos
        if ($sector->totalAsientos() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar un sector con asientos',
            ], 400);
        }

        $sector->delete();

        return response()->json([
            'message' => 'Sector eliminado correctamente',
        ]);
    }
}