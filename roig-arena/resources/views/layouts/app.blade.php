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
                    <a class="link" href="{{ route('login', [], false) }}">Login</a>
                @endguest

                @auth
                    <a class="link" href="{{ route('dashboard', [], false) }}">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</a>
                    <form method="POST" action="{{ route('logout.post', [], false) }}" style="display:inline; margin:0;">
                        @csrf
                        <button class="link" type="submit" style="background:none; border:0; padding:0; cursor:pointer;">Salir</button>
                    </form>
                @endauth

                <a class="link" href="{{ route('welcome', [], false) }}">Welcome original</a>

            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>
</html>
