<!-- estructura común de todas las páginas: cabecera, menú, estilos, pie, etc. -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Roig Arena')</title>
    <link rel="stylesheet" href="/css/site.css">
    @yield('page_styles')
</head>
<body class="@yield('body_class')">
    <header class="nav">
        <div class="container nav-inner">
            <div class="brand">Roig Arena · Consigue tu entrada</div>
            <nav class="links">
                <a class="link" href="{{ route('home', [], false) }}">Inicio</a>
                <a class="link" href="{{ route('eventos.index', [], false) }}">Eventos</a>
                @guest
                    <a class="link link-cta" href="{{ route('login', [], false) }}">Acceder</a>
                @endguest

                @auth
                    <a class="link" href="{{ route('dashboard', [], false) }}">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</a>
                @endauth

            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    @yield('page_scripts')
</body>
</html>
