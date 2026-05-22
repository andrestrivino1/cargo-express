@extends('layouts.app')

@section('content')
@php
    // Cliente actual del contenedor (vía OrdenServicio → Solicitud)
    $solicitudActual = $modelo->ordenServicio?->solicitud;
    $clienteActualId = $solicitudActual?->cliente_id;
    $clienteActualNombre = \App\Models\User::find($clienteActualId)?->name;

    // Buscar contenedores duplicados (mismo número, distinto cliente) por conflicto
    $duplicados = collect();
    if ($modelo->notas_conflicto) {
        $duplicados = \App\Models\Contenedor::query()
            ->where('numero', $modelo->numero)
            ->where('id', '!=', $modelo->id)
            ->with('ordenServicio.solicitud.cliente:id,name')
            ->get();
    }

    // Lista de clientes candidatos: el actual + los de los contenedores duplicados
    $candidatos = collect([$solicitudActual?->cliente])
        ->merge($duplicados->map(fn($c) => $c->ordenServicio?->solicitud?->cliente))
        ->filter()
        ->unique('id')
        ->values();
@endphp

<div class="container py-4" style="max-width:720px">
    <h1 class="h3 mb-3">Completar Contenedor {{ $modelo->numero }}</h1>
    @include('pendientes.completar._campos_comunes')

    @if ($modelo->notas_conflicto)
        <div class="alert alert-warning">
            <strong>⚠ Conflicto detectado:</strong><br>
            {{ $modelo->notas_conflicto }}
        </div>

        @if ($duplicados->isNotEmpty())
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning bg-opacity-25">
                    <strong>Contenedor(es) duplicado(s) en otro(s) cliente(s)</strong>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($duplicados as $dup)
                        <li class="list-group-item">
                            <strong>{{ $dup->ordenServicio?->solicitud?->cliente?->name ?? '?' }}</strong>
                            <span class="text-muted small">— Contenedor #{{ $dup->id }} con {{ $dup->referencias()->count() }} referencia(s)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if (str_starts_with($modelo->numero, 'PEND-'))
        <div class="alert alert-info">
            Este contenedor llegó del Excel sin número. Edita el campo "Número real" abajo con el código ISO correcto.
        </div>
    @endif

    <form method="POST" action="{{ route('pendientes.actualizar', ['type' => $tipoSlug, 'id' => $modelo->id]) }}">
        @csrf

        @if (in_array('numero', $campos))
            <div class="mb-3">
                <label class="form-label">Número real del contenedor *</label>
                <input type="text" name="numero" class="form-control @error('numero') is-invalid @enderror"
                       value="{{ old('numero', str_starts_with($modelo->numero, 'PEND-') ? '' : $modelo->numero) }}"
                       placeholder="Ej: MRKU9517467" maxlength="20">
                @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-muted">Placeholder actual: {{ $modelo->numero }}</small>
            </div>
        @endif

        @if (in_array('placa_vehiculo', $campos))
            <div class="mb-3">
                <label class="form-label">Placa del vehículo *</label>
                <input type="text" name="placa_vehiculo" class="form-control @error('placa_vehiculo') is-invalid @enderror" required value="{{ old('placa_vehiculo') }}">
                @error('placa_vehiculo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        @if (in_array('tipo', $campos))
            <div class="mb-3">
                <label class="form-label">Tipo de contenedor</label>
                <select name="tipo" class="form-select">
                    <option value="">— Sin especificar —</option>
                    @foreach (['20','40','40HC','45HC','OTRO'] as $t)
                        <option value="{{ $t }}" @selected(old('tipo') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if (in_array('destino_salida', $campos))
            <div class="mb-3">
                <label class="form-label">Destino de salida</label>
                <input type="text" name="destino_salida" class="form-control" value="{{ old('destino_salida') }}">
            </div>
        @endif

        @if (in_array('notas_conflicto', $campos))
            <div class="mb-4 border-top pt-3">
                <h5 class="mb-3">Resolución del conflicto</h5>

                @if ($candidatos->count() > 1)
                    <div class="mb-3">
                        <label class="form-label">¿Cuál es el cliente correcto de este contenedor?</label>
                        <select name="cliente_correcto_id" class="form-select">
                            @foreach ($candidatos as $c)
                                <option value="{{ $c->id }}" @selected(old('cliente_correcto_id', $clienteActualId) == $c->id)>
                                    {{ $c->name }}
                                    @if ($c->id === $clienteActualId) (actual) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Si eliges un cliente distinto al actual, el contenedor (con sus referencias y tarjas) se reasigna a ese cliente.</small>
                    </div>

                    @if ($duplicados->isNotEmpty())
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="eliminar_duplicado" value="1" id="elimDup">
                            <label class="form-check-label" for="elimDup">
                                Eliminar los {{ $duplicados->count() }} contenedor(es) duplicado(s) listados arriba (recomendado tras decidir cuál es el correcto)
                            </label>
                        </div>
                    @endif
                @endif

                <div class="mb-3">
                    <label class="form-label">Nota / decisión documentada</label>
                    <textarea name="notas_conflicto" class="form-control" rows="3"
                              placeholder="Describe la decisión tomada (ej: 'Confirmado con cliente X, los registros de Y eran error de captura')">{{ old('notas_conflicto', $modelo->notas_conflicto) }}</textarea>
                </div>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('pendientes.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
