@extends('layouts.app')

@php
    $estadoEfectivo = $cita->estadoEfectivo();
    $registro = $cita->registroPorteria;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-calendar-check me-2"></i>Cita — <code>{{ $cita->numero_contenedor }}</code>
        <span class="badge bg-{{ $estadoEfectivo->color() }} ms-2">{{ $estadoEfectivo->label() }}</span>
    </h2>
    <div>
        @can('citas.editar')
        @if ($cita->puedeEditarse())
        <a href="{{ route('citas.editar', $cita) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        @endif
        @endcan
        <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-box-seam me-1"></i> Contenedor</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Número</dt>
                    <dd class="col-7"><code>{{ $cita->numero_contenedor }}</code></dd>

                    <dt class="col-5">Tipo</dt>
                    <dd class="col-7">{{ $cita->tipo->label() }}</dd>

                    <dt class="col-5">Tamaño</dt>
                    <dd class="col-7">{{ $cita->tamano->label() }}</dd>

                    <dt class="col-5">Condición</dt>
                    <dd class="col-7"><span class="badge bg-{{ $cita->condicion->color() }}">{{ $cita->condicion->label() }}</span></dd>

                    <dt class="col-5">Fecha esperada</dt>
                    <dd class="col-7">{{ $cita->fecha_esperada?->format('d/m/Y') }}</dd>

                    <dt class="col-5">BL / Ingreso</dt>
                    <dd class="col-7">
                        @can('ingreso.ver')
                        <a href="{{ route('ingreso.show', $cita->ingreso_id) }}">{{ $cita->ingreso?->bl ?? '—' }}</a>
                        @else
                        {{ $cita->ingreso?->bl ?? '—' }}
                        @endcan
                    </dd>

                    <dt class="col-5">Cliente</dt>
                    <dd class="col-7">{{ $cita->ingreso?->cliente?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-person-badge me-1"></i> Transporte</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Conductor</dt>
                    <dd class="col-7">{{ $cita->conductor_nombre }}</dd>

                    <dt class="col-5">Cédula</dt>
                    <dd class="col-7">{{ $cita->conductor_cedula_original ?? $cita->conductor_cedula }}</dd>

                    <dt class="col-5">Placa</dt>
                    <dd class="col-7"><strong>{{ $cita->placa_original ?? $cita->placa }}</strong></dd>

                    <dt class="col-5">Empresa</dt>
                    <dd class="col-7">{{ $cita->empresa }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if ($registro)
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <i class="bi bi-shield-check me-1"></i> Llegada confirmada en portería
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Llegó el <strong>{{ $registro->llegada_at?->format('d/m/Y H:i') }}</strong>,
                    atendida por <strong>{{ $registro->portero?->name ?? '—' }}</strong>.
                    @if ($registro->observaciones)
                    <br><span class="text-muted">{{ $registro->observaciones }}</span>
                    @endif
                </p>

                <div class="row g-2">
                    @foreach (\App\Enums\PorteriaFotoCategoria::cases() as $categoria)
                    @php $foto = $registro->fotoPorCategoria($categoria); @endphp
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100 text-center">
                            <div class="small text-muted mb-1"><i class="bi {{ $categoria->icono() }}"></i> {{ $categoria->label() }}</div>
                            @if ($foto)
                            <a href="{{ $foto->url }}" target="_blank">
                                <img src="{{ $foto->url }}" alt="{{ $categoria->label() }}" class="img-fluid rounded" style="max-height: 140px;">
                            </a>
                            @else
                            <span class="text-muted small">Sin registrar</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="card">
            <div class="card-body small text-muted">
                Creada por <strong>{{ $cita->creador?->name ?? '—' }}</strong> el {{ $cita->created_at?->format('d/m/Y H:i') }}.
                @if ($cita->editor)
                Última modificación por <strong>{{ $cita->editor->name }}</strong> el {{ $cita->updated_at?->format('d/m/Y H:i') }}.
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
