<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaginaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\EntradaController;

Route::get('/', [PaginaController::class, 'home'])->name('home');
Route::get('/eventos', [PaginaController::class, 'eventosIndex'])->name('eventos.index');
Route::get('/eventos/{evento}', [PaginaController::class, 'eventosShow'])->name('eventos.show');
Route::get('/compra/{evento}', [CompraController::class, 'show'])->name('compra.buy');
// Route::get('/compra/{evento}/setmap', [CompraController::class,'setmap'])->name('compra.setmap');

// Rutas de login
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

// Rutas de registro
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Ruta de logout
Route::post('/logout', [AuthController::class, 'logoutWeb'])->middleware('auth')->name('logout.post');

// Rutas informacion usuario
Route::view('/dashboard', 'auth.dashboard')->middleware('auth')->name('dashboard');
Route::view('/profile', 'auth.profile')->middleware('auth')->name('profile');
Route::get('/mis-eventos', [EventoController::class, 'misEventos'])->middleware('auth')->name('mis-eventos');
Route::get('/mis-eventos-info', [EventoController::class, 'misEventosInfo'])->middleware('auth')->name('mis-eventos.info');
Route::get('/mi-evento-info/{id}', [EventoController::class, 'miEventoInfo'])->middleware('auth')->name('mi-evento.info');
Route::delete('/entradas/{id}', [EntradaController::class, 'destroy'])->middleware('auth')->name('entradas.destroy');

// Rutas de pagos pendientes
Route::get('/mis-pagos-pendientes', [CompraController::class, 'misPagosPendientes'])->middleware('auth')->name('mis-pagos-pendientes');
Route::post('/mis-pagos-pendientes/pagar', [CompraController::class, 'procesarPagoPendiente'])->middleware('auth')->name('mis-pagos-pendientes.pagar');

// Rutas admin (web)
Route::middleware(['auth', 'admin'])
	->prefix('admin')
	->name('admin.')
	->group(function () {
		Route::view('/eventos/create', 'eventos.create')->name('eventos.create');
		Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
		Route::patch('/eventos/{id}', [EventoController::class, 'update'])->name('eventos.update');
	});

// Ruta opcional para conservar la vista inicial de Laravel como referencia.
Route::view('/welcome', 'welcome')->name('welcome');
