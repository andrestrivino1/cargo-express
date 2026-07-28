@extends('layouts.app')

@section('content')
{{--
    Vista pensada para el teléfono del portero: buscador grande arriba y
    tarjetas en vez de tabla. Solo muestra citas de HOY.
--}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-shield-check me-2"></i>Portería</h2>
    <span class="badge bg-secondary fs-6">{{ today()->format('d/m/Y') }}</span>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-9">
                <input type="search" name="q" value="{{ $busqueda }}" class="form-control form-control-lg"
                       placeholder="Placa o número de contenedor" autocomplete="off" autofocus>
            </div>
            <div class="col-3">
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
        <div class="form-text mt-2">
            Da igual cómo esté escrito: <code>abc 123</code>, <code>ABC-123</code> y <code>ABC123</code> encuentran lo mismo.
        </div>
    </div>
</div>

{{-- ── Citas de HOY: las accionables ── --}}
<h5 class="text-uppercase text-muted small fw-bold mb-2">
    <i class="bi bi-calendar-day me-1"></i> Hoy
</h5>

@if ($citas->isEmpty())
<div class="card mb-4">
    <div class="card-body text-center py-4">
        @if ($busqueda)
        <i class="bi bi-x-octagon d-block fs-1 text-warning mb-3"></i>
        <h4>Sin cita para hoy</h4>
        <p class="text-muted">
            No hay ninguna cita para <strong>{{ $busqueda }}</strong> en la fecha de hoy.
            @if ($proximas->isNotEmpty())
            <br><span class="text-info">Pero sí aparece más abajo, en próximas citas.</span>
            @endif
        </p>
        @can('porteria.registrar')
        <a href="{{ route('porteria.novedad.create', ['placa' => $busqueda]) }}" class="btn btn-warning btn-lg">
            <i class="bi bi-flag me-1"></i> Reportar novedad
        </a>
        @endcan
        @else
        <i class="bi bi-calendar-x d-block fs-1 text-muted mb-3"></i>
        <h4>Sin citas para hoy</h4>
        <p class="text-muted mb-0">No hay ningún vehículo agendado para la fecha de hoy.</p>
        @endif
    </div>
</div>
@else
<div class="row g-3 mb-4">
    @foreach ($citas as $cita)
    @php $atendida = $cita->estaAtendida(); @endphp
    <div class="col-12 col-md-6">
        <a href="{{ route('porteria.show', $cita) }}" class="text-decoration-none">
            <div class="card h-100 {{ $atendida ? 'border-success' : 'border-primary' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h4 class="mb-0"><code>{{ $cita->numero_contenedor }}</code></h4>
                        <span class="badge bg-{{ $cita->estadoEfectivo()->color() }}">{{ $cita->estadoEfectivo()->label() }}</span>
                    </div>
                    <div class="fs-5 fw-bold text-body">{{ $cita->placa_original ?? $cita->placa }}</div>
                    <div class="text-muted">
                        {{ $cita->conductor_nombre }} · {{ $cita->empresa }}
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-{{ $cita->condicion->color() }}">{{ $cita->condicion->label() }}</span>
                        <span class="badge bg-light text-dark">{{ $cita->tipo->label() }}</span>
                        <span class="badge bg-light text-dark">{{ $cita->tamano->label() }}</span>
                    </div>
                    @if ($atendida)
                    <div class="small text-success mt-2">
                        <i class="bi bi-check-circle"></i>
                        Llegó a las {{ $cita->registroPorteria?->llegada_at?->format('H:i') }}
                    </div>
                    @endif
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- ── Próximas citas: solo seguimiento, no se confirman ── --}}
@if ($proximas->isNotEmpty())
<h5 class="text-uppercase text-muted small fw-bold mb-2">
    <i class="bi bi-calendar-week me-1"></i> Próximas citas
    <span class="badge bg-secondary ms-1">{{ $proximas->count() }}</span>
</h5>
<p class="text-muted small mb-2">
    Solo para seguimiento: se confirman el día que corresponde.
</p>

<div class="card mb-4">
    <div class="list-group list-group-flush">
        @foreach ($proximas as $proxima)
        <a href="{{ route('porteria.show', $proxima) }}" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">
                        <code>{{ $proxima->numero_contenedor }}</code>
                        <span class="ms-2">{{ $proxima->placa_original ?? $proxima->placa }}</span>
                    </div>
                    <div class="text-muted small">
                        {{ $proxima->conductor_nombre }} · {{ $proxima->empresa }}
                        · <span class="badge bg-{{ $proxima->condicion->color() }}">{{ $proxima->condicion->label() }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">{{ $proxima->fecha_esperada->format('d/m') }}</div>
                    <div class="text-muted small">{{ $proxima->fecha_esperada->diffForHumans(today(), ['parts' => 1]) }}</div>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@can('porteria.registrar')
<div class="text-center mt-3">
    <a href="{{ route('porteria.novedad.create', $busqueda ? ['placa' => $busqueda] : []) }}" class="btn btn-outline-warning">
        <i class="bi bi-flag me-1"></i> Reportar vehículo sin cita
    </a>
</div>
@endcan
@endsection
