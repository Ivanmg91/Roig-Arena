@extends('layouts.app')

@section('title', 'Perfil | Roig Arena')
@section('body_class', 'dashboard-page')

@section('page_styles')
	<link rel="stylesheet" href="/css/pages/dashboard.css">
@endsection

@section('content')
    @php
		$redirectTo = request('redirect', route('home', [], false));
	@endphp

    <section class="dashboard-shell" aria-label="Perfil de usuario">
        <h1 class="dashboard-title">Bienvenido, {{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</h1>
        <p class="dashboard-text">
            Desde tu perfil puedes gestionar tus datos personales, revisar tus compras y acceder a tus entradas.
        </p>

        <div class="dashboard-actions">
            <a href="#" class="dashboard-action">Mis datos</a>
            <a href="#" class="dashboard-action">Mis compras</a>
            <a href="#" class="dashboard-action">Mis entradas</a>
        </div>
    </section>
@endsection
