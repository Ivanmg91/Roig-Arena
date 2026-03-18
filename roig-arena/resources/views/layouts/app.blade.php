<!-- estructura común de todas las páginas: cabecera, menú, estilos, pie, etc. -->
 
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Roig Arena')</title>
    <link rel="stylesheet" href="/css/site.css">
</head>
<body class="@yield('body_class')">
    <header class="nav">
        <div class="container nav-inner">
            <div class="brand">Roig Arena · Consigue tu entrada</div>
            <nav class="links">
                <a class="link" href="{{ route('home', [], false) }}">Inicio</a>
                <a class="link" href="{{ route('eventos.index', [], false) }}">Eventos</a>
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