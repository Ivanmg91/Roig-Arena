@extends('layouts.app')

@section('title', 'Crear evento | Roig Arena')

@section('content')
    <section class="card">
        <h1>Crear evento</h1>
        <p class="muted">Formulario base para alta de eventos.</p>

        @if (session('success'))
            <div class="card" style="margin-top: 1rem; border-color: #2e7d32;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="card" style="margin-top: 1rem; border-color: #d9534f;">
                <strong>Revisa los siguientes errores:</strong>
                <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.eventos.store', [], false) }}" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf

            <div>
                <label for="nombre"><strong>Nombre del evento</strong></label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="descripcion_corta"><strong>Descripción corta</strong></label>
                <input id="descripcion_corta" name="descripcion_corta" type="text" value="{{ old('descripcion_corta') }}" maxlength="255" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="descripcion_larga"><strong>Descripción larga</strong></label>
                <textarea id="descripcion_larga" name="descripcion_larga" rows="4" required style="display:block;width:100%;margin-top:.35rem;">{{ old('descripcion_larga') }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                <div>
                    <label for="fecha"><strong>Fecha</strong></label>
                    <input id="fecha" name="fecha" type="date" value="{{ old('fecha') }}" required style="display:block;width:100%;margin-top:.35rem;">
                </div>

                <div>
                    <label for="hora"><strong>Hora</strong></label>
                    <input id="hora" name="hora" type="time" value="{{ old('hora') }}" style="display:block;width:100%;margin-top:.35rem;">
                </div>
            </div>

            <div>
                <label for="poster_url"><strong>URL del póster</strong></label>
                <input id="poster_url" name="poster_url" type="url" value="{{ old('poster_url') }}" placeholder="https://..." style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="poster_ancho_url"><strong>URL del póster ancho</strong></label>
                <input id="poster_ancho_url" name="poster_ancho_url" type="url" value="{{ old('poster_ancho_url') }}" placeholder="https://..." style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('eventos.index', [], false) }}" class="btn">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
