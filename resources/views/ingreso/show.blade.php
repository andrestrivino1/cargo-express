@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso — BL {{ $ingreso->bl }}</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('ingreso.index') }}">Ingreso</a></li>
                <li class="breadcrumb-item active">BL {{ $ingreso->bl }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        @role('administrador|coordinador')
        <a href="{{ route('ingreso.editar', $ingreso) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Editar</a>
        @endrole
        @can('ingreso.eliminar')
        @if (empty($bloqueosEliminar))
        <form action="{{ route('ingreso.destroy', $ingreso) }}" method="POST"
              onsubmit="return confirm('¿Eliminar el ingreso del BL {{ $ingreso->bl }}?\n\nSe borrarán sus {{ $ingreso->contenedores->count() }} contenedor(es), sus referencias y sus documentos. Esta acción no se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> Eliminar</button>
        </form>
        @else
        <button type="button" class="btn btn-outline-danger" disabled
                title="No se puede eliminar: la mercancía ya se movió">
            <i class="bi bi-trash me-1"></i> Eliminar
        </button>
        @endif
        @endcan
    </div>
</div>

{{-- Si el ingreso no se puede borrar, se explica exactamente por qué --}}
@can('ingreso.eliminar')
@if (! empty($bloqueosEliminar))
<div class="alert alert-secondary">
    <strong><i class="bi bi-lock me-1"></i> Este ingreso no se puede eliminar</strong>
    <ul class="mb-0 mt-2">
        @foreach ($bloqueosEliminar as $bloqueo)
        <li>{{ $bloqueo }}</li>
        @endforeach
    </ul>
    <div class="small text-muted mt-2">
        Borrarlo dejaría registros huérfanos y descuadraría el inventario.
    </div>
</div>
@endif
@endcan

@if ($ingreso->bl_por_confirmar)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    El <strong>BL es provisional</strong> (se usó el número de contenedor al importar).
    @role('administrador|coordinador')<a href="{{ route('ingreso.editar', $ingreso) }}" class="alert-link">Edita el ingreso</a> para poner el BL real.@else Pide a un administrador/coordinador que lo confirme.@endrole
</div>
@endif

@php
    // Compatibilidad: documentos del ingreso (nuevo) + de sus contenedores (legados feature 005)
    $documentos = $ingreso->documentos->concat($ingreso->contenedores->flatMap->documentos);
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Datos del ingreso</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>BL:</strong> {{ $ingreso->bl }}</li>
                <li class="list-group-item"><strong>Cliente:</strong> {{ $ingreso->cliente?->name ?? '—' }}</li>
                <li class="list-group-item"><strong>Fecha de ingreso:</strong> {{ $ingreso->fecha_ingreso?->format('d/m/Y') }}</li>
                <li class="list-group-item"><strong>Contenedores:</strong> {{ $ingreso->contenedores->count() }}</li>
            </ul>
        </div>

        <div class="card mb-3">
            <div class="card-header">Documentos soporte</div>
            <ul class="list-group list-group-flush">
                @forelse ($documentos as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi {{ $doc->icono }} me-1"></i> {{ \App\Enums\DocumentoCategoria::tryFrom($doc->categoria ?? '')?->label() ?? $doc->nombre }}</span>
                    <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                </li>
                @empty
                <li class="list-group-item text-muted">Sin documentos.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        @foreach ($ingreso->contenedores as $contenedor)
        @php
            $cita = $contenedor->citaVigente;
            $registro = $cita?->registroPorteria;
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-box-seam me-1"></i> Contenedor {{ $contenedor->numero }}</span>
                <span class="text-muted small">{{ $contenedor->tipo_mercancia }}</span>
            </div>

            {{-- Estado de la cita de este contenedor (cadena Ingreso -> Cita -> Portería) --}}
            @if (config('modulos.citas'))
            <div class="card-body border-bottom py-2 bg-light">
                @if ($cita)
                    <span class="badge bg-{{ $cita->estadoEfectivo()->color() }}">
                        <i class="bi bi-calendar-check"></i> Cita {{ $cita->estadoEfectivo()->label() }}
                    </span>
                    <span class="text-muted small ms-1">
                        esperada el {{ $cita->fecha_esperada?->format('d/m/Y') }} · {{ $cita->placa_original ?? $cita->placa }} · {{ $cita->empresa }}
                    </span>

                    @if ($registro)
                    <div class="mt-2 small">
                        <i class="bi bi-shield-check text-success"></i>
                        Llegó el <strong>{{ $registro->llegada_at?->format('d/m/Y H:i') }}</strong>
                        (atendió {{ $registro->portero?->name ?? '—' }}) —
                        @foreach (\App\Enums\PorteriaFotoCategoria::cases() as $categoria)
                            @php $foto = $registro->fotoPorCategoria($categoria); @endphp
                            @if ($foto)
                            <a href="{{ $foto->url }}" target="_blank" class="me-2" title="{{ $categoria->label() }}">
                                <i class="bi {{ $categoria->icono() }}"></i>
                            </a>
                            @endif
                        @endforeach
                    </div>
                    @endif

                    @can('citas.ver')
                    <a href="{{ route('citas.show', $cita) }}" class="small ms-1">Ver cita</a>
                    @endcan
                @else
                    <span class="badge bg-secondary"><i class="bi bi-calendar-x"></i> Sin cita agendada</span>
                    @can('citas.crear')
                    <a href="{{ route('citas.create') }}" class="small ms-2">Agendar</a>
                    @endcan
                @endif
            </div>
            @endif
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Referencia</th><th>Descripción</th><th>Unidad</th><th>Peso</th><th>Cantidad</th><th>Ubicación</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($contenedor->referencias as $ref)
                        <tr>
                            <td>{{ $ref->codigo }}</td>
                            <td>{{ $ref->descripcion }}</td>
                            <td>{{ $ref->unidad_medida }}</td>
                            <td>{{ $ref->peso }}</td>
                            <td>{{ $ref->cantidad_actual }}</td>
                            <td>
                                @if ($ref->ubicacionPatio)
                                    {{ $ref->ubicacionPatio->modulo }} - {{ $ref->ubicacionPatio->posicion }}
                                @else
                                    <span class="badge bg-warning text-dark">Sin ubicar</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
