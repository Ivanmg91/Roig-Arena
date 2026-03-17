<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    /**
     * Listar mis entradas
     */
    public function index(Request $request)
    {
        $entradas = $request->user()
            ->entradas()
            ->with(['evento', 'asiento.sector'])
            ->latest()
            ->get()
            ->map(function ($entrada) {
                return [
                    'id' => $entrada->id,
                    'codigo_qr' => $entrada->codigo_qr,
                    'evento' => $entrada->evento->nombre,
                    'fecha' => $entrada->evento->fecha->format('d/m/Y'),
                    'hora' => $entrada->evento->hora,
                    'asiento' => $entrada->asiento->nombreCompleto(),
                    'precio' => $entrada->precioFormateado(),
                    'valida' => $entrada->esValida(),
                ];
            });

        return response()->json([
            'data' => $entradas,
        ]);
    }

    /**
     * Ver detalle de una entrada
     */
    public function show($id)
    {
        $entrada = Entrada::where('id', $id)
            ->where('user_id', auth()->id())
            ->with(['evento', 'asiento.sector'])
            ->firstOrFail();

        return response()->json([
            'data' => $entrada->informacionCompleta(),
        ]);
    }
}