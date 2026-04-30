@extends('layouts.app')

@section('title', $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
    <link rel="stylesheet" href="/css/pages/show.css">
@endsection

@section('content')
    <!-- Imagen hero del evento -->
    <div class="event-hero">
        @if($evento->poster_ancho_url)
            <img src="{{ $evento->poster_ancho_url }}" alt="{{ $evento->nombre }}" class="event-hero-image">
        @else
            <div class="event-hero-image event-no-image">
                Sin imagen disponible
            </div>
        @endif
        <div class="event-hero-overlay"></div>
    </div>

    <!-- Información principal del evento -->
    <div class="event-info-grid">
        <!-- Información del evento -->
        <section class="event-info-section">
            <div class="event-title-editor" data-event-title-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                <h2 class="event-title-display" data-event-title-display>{{ $evento->nombre }}</h2>

                @auth
                    @if(auth()->user()->isAdmin())
                        <button type="button" class="event-title-edit-button" data-event-title-toggle aria-label="Editar nombre del evento">
                            ✎
                        </button>

                        <form class="event-title-form" data-event-title-form hidden>
                            @csrf
                            @method('PATCH')
                            <input
                                type="text"
                                name="nombre"
                                value="{{ $evento->nombre }}"
                                maxlength="255"
                                required
                                class="event-title-input"
                                data-event-title-input
                                aria-label="Nombre del evento"
                            >
                        </form>
                    @endif
                @endauth
            </div>

            @if($evento->fecha || $evento->hora)
                <div class="event-meta-item">
                    <span class="event-meta-label">Fecha</span>
                    <span class="event-meta-value">
                        @if($evento->fecha)
                            {{ $evento->fecha->format('d/m/Y') }}
                        @else
                            Por confirmar
                        @endif
                    </span>
                </div>

                <div class="event-meta-item">
                    <span class="event-meta-label">Hora</span>
                    <span class="event-meta-value">
                        @if($evento->hora)
                            {{ $evento->hora->format('H:i') }}
                        @else
                            Por confirmar
                        @endif
                    </span>
                </div>
            @endif

            @if($evento->descripcion_corta)
                <div class="event-description-block">
                    <p class="event-description">{{ $evento->descripcion_corta }}</p>
                </div>
            @endif
        </section>

        <!-- Artistas -->
        <section class="event-info-section">
            <h2>Artistas</h2>

            @forelse($evento->artistas as $artista)
                <div class="artist-card">
                    <div class="artist-card-header">
                        @if($artista->imagen_url)
                            <img src="{{ $artista->imagen_url }}" alt="{{ $artista->nombre }}" class="artist-image">
                        @endif
                        <div>
                            <p class="artist-name">{{ $artista->nombre }}</p>
                            @if($artista->descripcion)
                                <p class="artist-description">{{ $artista->descripcion }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="muted">No hay artistas asignados a este evento.</p>
            @endforelse
        </section>
    </div>

    <!-- Descripción detallada -->
    @if($evento->descripcion_larga)
        <section class="card section-gap">
            <div class="section-divider">
                <span>SOBRE EL EVENTO</span>
            </div>
            <p class="event-description">{{ $evento->descripcion_larga }}</p>
        </section>
    @endif

    <!-- Precios y disponibilidad -->
    <section class="card section-gap">
        <div class="section-divider">
            <span>PRECIOS</span>
        </div>
        <p class="muted event-prices-intro">Selecciona tu sector y compra tu entrada</p>

        <table class="pricing-table">
            <thead>
                <tr>
                    <th>Sector</th>
                    <th>Precio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($precios as $precio)
                    <tr>
                        <td>
                            <strong>{{ $precio->sector->nombre }}</strong>
                            @if($precio->sector->descripcion)
                                <br>
                                <span class="muted">{{ $precio->sector->descripcion }}</span>
                            @endif
                        </td>
                        <td class="price-highlight">
                            {{ number_format($precio->precio, 2, ',', '.') }}€
                        </td>
                        <td>
                            @if($precio->disponible)
                                <span class="badge badge-success">Disponible</span>
                            @else
                                <span class="badge badge-danger">Agotado</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($evento->sectoresDisponibles()->count() > 0)
            <div class="cta-section">
                <a href="{{ route('compra.buy', ['evento' => $evento->id], false) }}" class="btn btn-primary">
                    Comprar Entradas
                </a>
                <a href="{{ route('eventos.index', [], false) }}" class="btn btn-alt">
                    Volver
                </a>
            </div>
        @else
            <div class="cta-section">
                <p class="muted">No hay entradas disponibles para este evento en este momento.</p>
                <a href="{{ route('eventos.index', [], false) }}" class="btn btn-alt">
                    Volver al listado
                </a>
            </div>
        @endif
    </section>

@endsection

@section('page_scripts')
    @auth
        @if(auth()->user()->isAdmin())
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const editor = document.querySelector('[data-event-title-editor]');

                    if (!editor) {
                        return;
                    }

                    const toggleButton = editor.querySelector('[data-event-title-toggle]');
                    const titleDisplay = editor.querySelector('[data-event-title-display]');
                    const form = editor.querySelector('[data-event-title-form]');
                    const input = editor.querySelector('[data-event-title-input]');
                    const updateUrl = editor.dataset.updateUrl;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                    if (!toggleButton || !titleDisplay || !form || !input || !updateUrl || !csrfToken) {
                        return;
                    }

                    const openEditor = () => {
                        titleDisplay.hidden = true;
                        toggleButton.hidden = true;
                        form.hidden = false;
                        input.hidden = false;
                        input.value = titleDisplay.textContent.trim();
                        input.focus();
                        input.select();
                    };

                    const closeEditor = () => {
                        form.hidden = true;
                        input.hidden = true;
                        toggleButton.hidden = false;
                        titleDisplay.hidden = false;
                        input.setCustomValidity('');
                    };

                    toggleButton.addEventListener('click', openEditor);

                    input.addEventListener('input', () => {
                        input.setCustomValidity('');
                    });

                    input.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            input.value = titleDisplay.textContent.trim();
                            closeEditor();
                        }
                    });

                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const newName = input.value.trim();

                        if (!newName) {
                            input.setCustomValidity('El nombre no puede estar vacío.');
                            input.reportValidity();
                            return;
                        }

                        try {
                            const response = await fetch(updateUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                body: new FormData(form),
                            });

                            const payload = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const message = payload?.message || payload?.error || 'No se pudo actualizar el nombre.';
                                input.setCustomValidity(message);
                                input.reportValidity();
                                input.focus();
                                return;
                            }

                            const updatedName = payload?.data?.nombre ?? newName;
                            titleDisplay.textContent = updatedName;
                            input.value = updatedName;
                            document.title = `${updatedName} | Roig Arena`;
                            closeEditor();
                        } catch (error) {
                            input.setCustomValidity('Error de red al actualizar el evento.');
                            input.reportValidity();
                        }
                    });
                });
            </script>
        @endif
    @endauth
@endsection
