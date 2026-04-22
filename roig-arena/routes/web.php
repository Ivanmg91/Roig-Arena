<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaginaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/', [PaginaController::class, 'home'])->name('home');
Route::get('/eventos', [PaginaController::class, 'eventosIndex'])->name('eventos.index');
Route::get('/eventos/{evento}', [PaginaController::class, 'eventosShow'])->name('eventos.show');
Route::get('/compra/{evento}', [CompraController::class, 'show'])->name('compra.buy');
// Route::get('/compra/{evento}/setmap', [CompraController::class,'setmap'])->name('compra.setmap');

// Rutas de autenticación y registro
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logoutWeb'])->middleware('auth')->name('logout.post');

Route::view('/dashboard', 'auth.dashboard')->middleware('auth')->name('dashboard');
Route::view('/profile', 'auth.profile')->middleware('auth')->name('profile');
Route::get('/mis-eventos', [PaginaController::class, 'misEventos'])->middleware('auth')->name('mis-eventos');


// Ruta opcional para conservar la vista inicial de Laravel como referencia.
Route::view('/welcome', 'welcome')->name('welcome');
