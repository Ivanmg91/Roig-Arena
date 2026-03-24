<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Evento;

class CompraController extends Controller
{
    /**
     * Mostrar formulario de compra de entradas
     */
    public function show(Evento $evento)
    {
        $evento->load(['precios.sector', 'artistas']);
        
        $sectoresDisponibles = $evento->sectoresDisponibles();

        return view('compra.show', [
            'evento' => $evento,
            'sectoresDisponibles' => $sectoresDisponibles,
        ]);
    }
}
