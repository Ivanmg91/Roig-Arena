<?php

namespace App\Http\Controllers;

use App\Http\Resources\EntradaResource;
use App\Services\CompraService;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    /**
     * Confirmar compra de reservas
     */
    public function store(Request $request, CompraService $service)
    {
        $request->validate([
            'reservas' => 'required|array|min:1',
            'reservas.*' => 'exists:estado_asientos,id',
        ]);

        try {
            $entradas = $service->procesarCompra(
                $request->reservas,
                auth()->id()
            );

            return response()->json([
                'data' => EntradaResource::collection($entradas),
                'message' => 'Compra realizada correctamente',
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}