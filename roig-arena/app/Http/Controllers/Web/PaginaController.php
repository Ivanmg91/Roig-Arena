<?php
// utilizamos los models y sus funciones para ejecutar funciones y usarlas en las vistas como home

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Evento;

class PaginaController extends Controller
{
    public function home()
    {
        $proximosEventos = Evento::futuros()->get();

        return view('home', [
            'proximosEventos' => $proximosEventos,
        ]);
    }

    public function eventosIndex()
    {
        $eventos = Evento::futuros()
            ->with(['precios.sector'])
            ->paginate(9);

        return view('eventos.index', [
            'eventos' => $eventos,
        ]);
    }

    public function eventosShow(Evento $evento)
    {
        $evento->load(['precios.sector', 'artistas']);

        return view('eventos.show', [
            'evento' => $evento,
        ]);
    }

    public function misEventos()
    {
        $miseventos = Evento::whereHas('entradas', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['precios.sector'])
            ->orderBy('fecha', 'asc')
            ->paginate(9);

        return view('auth.mis-eventos', [
            'miseventos' => $miseventos,
        ]);
    }
}
