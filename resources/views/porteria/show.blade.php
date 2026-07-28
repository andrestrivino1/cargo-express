@extends('layouts.app')

@php
    $registro = $cita->registroPorteria;
    $atendida = $cita->estaAtendida();
    $esDeHoy = $cita->esDeHoy();
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-shield-check me-2"></i>Control en puerta</h2>
    <a href="{{ route('porteria.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

@unless ($esDeHoy)
<div class="alert alert-info">
    <i class="bi bi-calendar-week me-1"></i>
    Esta cita es para el <strong>{{ $cita->fecha_esperada->format('d/m/Y') }}</strong>
    ({{ $cita->fecha_esperada->diffForHumans(today(), ['parts' => 1]) }}).
    Se consulta para seguimiento; se confirma el día que corresponde.
</div>
@endunless

{{-- Datos esperados, para contrastarlos con el vehículo que está enfrente --}}
<div class="card mb-3 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-clipboard-check me-1"></i> Lo que dice la cita
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="text-muted small">Contenedor</div>
                <div class="fs-5"><code>{{ $cita->numero_contenedor }}</code></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Placa</div>
                <div class="fs-5 fw-bold">{{ $cita->placa_original ?? $cita->placa }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Conductor</div>
                <div>{{ $cita->conductor_nombre }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Cédula</div>
                <div>{{ $cita->conductor_cedula_original ?? $cita->conductor_cedula }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Empresa</div>
                <div>{{ $cita->empresa }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Condición</div>
                <div><span class="badge bg-{{ $cita->condicion->color() }}">{{ $cita->condicion->label() }}</span></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Tipo</div>
                <div>{{ $cita->tipo->label() }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Tamaño</div>
                <div>{{ $cita->tamano->label() }}</div>
            </div>
        </div>
    </div>
</div>

@if ($atendida)
{{-- Modo consulta: la llegada ya se confirmó y no se vuelve a confirmar --}}
<div class="card border-success">
    <div class="card-header bg-success text-white">
        <i class="bi bi-check-circle me-1"></i> Llegada ya confirmada
    </div>
    <div class="card-body">
        <p>
            Confirmada el <strong>{{ $registro?->llegada_at?->format('d/m/Y H:i') }}</strong>
            por <strong>{{ $registro?->portero?->name ?? '—' }}</strong>.
            @if ($registro?->observaciones)
            <br><span class="text-muted">{{ $registro->observaciones }}</span>
            @endif
        </p>

        <div class="row g-2">
            @foreach ($categorias as $categoria)
            @php $foto = $registro?->fotoPorCategoria($categoria); @endphp
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center h-100">
                    <div class="small text-muted mb-1"><i class="bi {{ $categoria->icono() }}"></i> {{ $categoria->label() }}</div>
                    @if ($foto)
                    <a href="{{ $foto->url }}" target="_blank">
                        <img src="{{ $foto->url }}" alt="{{ $categoria->label() }}" class="img-fluid rounded" style="max-height: 160px;">
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
@elseif (! $esDeHoy)
{{-- Cita futura: solo consulta. Adelantar una llegada exige reprogramar. --}}
<div class="card border-info">
    <div class="card-body text-center py-4">
        <i class="bi bi-hourglass-split d-block fs-1 text-info mb-3"></i>
        <h5>Todavía no se puede confirmar</h5>
        <p class="text-muted mb-3">
            La confirmación de llegada se habilita el <strong>{{ $cita->fecha_esperada->format('d/m/Y') }}</strong>.
            Si el vehículo ya está en la puerta, pide al área de citas que reprograme la cita.
        </p>
        @can('porteria.registrar')
        <a href="{{ route('porteria.novedad.create', ['placa' => $cita->placa_original ?? $cita->placa]) }}" class="btn btn-warning">
            <i class="bi bi-flag me-1"></i> Reportar que llegó antes de tiempo
        </a>
        @endcan
    </div>
</div>
@else
{{-- Confirmación: las cuatro evidencias son obligatorias --}}
@can('porteria.registrar')
<form method="POST" action="{{ route('porteria.llegada', $cita) }}" enctype="multipart/form-data" id="form-llegada">
    @csrf

    <div class="card">
        <div class="card-header">
            <i class="bi bi-camera me-1"></i> Evidencias obligatorias
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($categorias as $categoria)
                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="bi {{ $categoria->icono() }} me-1"></i>
                        {{ $categoria->label() }} <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="{{ $categoria->campo() }}"
                           class="form-control form-control-lg @error($categoria->campo()) is-invalid @enderror"
                           accept="image/*" capture="environment" required>
                    @error($categoria->campo()) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @endforeach

                <div class="col-12">
                    <label class="form-label">Observaciones <span class="text-muted">(opcional)</span></label>
                    <textarea name="observaciones" rows="2" maxlength="500"
                              class="form-control @error('observaciones') is-invalid @enderror"
                              placeholder="Diferencias con lo agendado, estado del sello, etc.">{{ old('observaciones') }}</textarea>
                    @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success btn-lg w-100" id="btn-confirmar">
                <i class="bi bi-check-circle me-1"></i> Confirmar llegada
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
// Evita el doble envío por toque repetido en móvil. La garantía real está en la
// restricción UNIQUE de porteria_registros.cita_id.
document.getElementById('form-llegada')?.addEventListener('submit', function () {
    const boton = document.getElementById('btn-confirmar');
    boton.disabled = true;
    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Subiendo evidencias…';
});
</script>
@endpush
@else
<div class="alert alert-secondary">No tienes permiso para confirmar llegadas.</div>
@endcan
@endif
@endsection
