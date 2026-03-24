<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaginaController;
use App\Http\Controllers\Web\CompraController;

Route::get('/', [PaginaController::class, 'home'])->name('home');
Route::get('/eventos', [PaginaController::class, 'eventosIndex'])->name('eventos.index');
Route::get('/eventos/{evento}', [PaginaController::class, 'eventosShow'])->name('eventos.show');
Route::get('/compra/{evento}', [CompraController::class, 'show'])->name('compra.buy');

// Ruta opcional para conservar la vista inicial de Laravel como referencia.
Route::view('/welcome', 'welcome')->name('welcome');
