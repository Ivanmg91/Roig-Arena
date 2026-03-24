<?php

namespace App\Http\Controllers;

use App\Models\Artista;
use App\Models\Evento;
use App\Http\Resources\ArtistaResource;
use Illuminate\Http\Request;

class ArtistaController extends Controller
{
    /**
     * Listar todos los artistas
     */
    public function index()
    {
        $artistas = Artista::with('evento')->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }

    /**
     * Listar artistas por evento
     */
    public function porEvento($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $artistas = $evento->artistas()->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }

    /**
     * Ver detalle de un artista
     */
    public function show($id)
    {
        $artista = Artista::with('evento')->findOrFail($id);

        return response()->json([
            'data' => new ArtistaResource($artista),
        ]);
    }

    /**
     * Crear artista (admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'evento_id' => 'required|exists:eventos,id',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url',
        ]);

        $artista = Artista::create($request->all());

        return response()->json([
            'data' => new ArtistaResource($artista),
            'message' => 'Artista creado correctamente',
        ], 201);
    }

    /**
     * Actualizar artista (admin)
     */
    public function update(Request $request, $id)
    {
        $artista = Artista::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'evento_id' => 'sometimes|exists:eventos,id',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url',
        ]);

        $artista->update($request->all());

        return response()->json([
            'data' => new ArtistaResource($artista),
            'message' => 'Artista actualizado correctamente',
        ]);
    }

    /**
     * Eliminar artista (admin)
     */
    public function destroy($id)
    {
        $artista = Artista::findOrFail($id);
        $artista->delete();

        return response()->json([
            'message' => 'Artista eliminado correctamente',
        ]);
    }

    /**
     * Buscar artista por nombre
     */
    public function buscar(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query) {
            return response()->json([
                'error' => 'El parámetro "q" es requerido',
            ], 400);
        }

        $artistas = Artista::where('nombre', 'like', "%{$query}%")
            ->with('evento')
            ->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }
}
