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
                <a class="link" id="loginNavLink" href="{{ route('login', [], false) }}">Login</a>
                <a class="link" id="userNavLink" href="/mi-cuenta" hidden></a>
                <a class="link" href="{{ route('welcome', [], false) }}">Welcome original</a>

            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginNavLink = document.getElementById('loginNavLink');
            const userNavLink = document.getElementById('userNavLink');

            if (!loginNavLink || !userNavLink) {
                return;
            }

            try {
                const rawUser = localStorage.getItem('sanctum_user');

                if (!rawUser) {
                    return;
                }

                const user = JSON.parse(rawUser);
                const userName = (user?.nombre ?? '').trim();
                const userApellido = (user?.apellido ?? '').trim();
                const displayName = `${userName} ${userApellido}`.trim() || (user?.email ?? '').trim();

                if (!displayName) {
                    return;
                }

                userNavLink.textContent = displayName;
                userNavLink.hidden = false;
                loginNavLink.hidden = true;
            } catch (error) {
                localStorage.removeItem('sanctum_user');
            }
        });
    </script>
</body>
</html>
