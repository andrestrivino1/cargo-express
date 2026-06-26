@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-right me-2"></i>Detalle Salida</h2>
    <div class="d-flex gap-2">
        @role('administrador|coordinador')
            @php($salidaEvent = $contenedor->gateEvents->firstWhere('tipo', \App\Enums\GateEventTipo::GateOut))
            @if($salidaEvent)
                <a href="{{ route('gate-out.editar', $salidaEvent) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Editar salida
                </a>
            @endif
        @endrole
        <a href="{{ route('gate-out.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<!-- Contenedor Details Card -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Contenedor {{ $contenedor->numero }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p class="text-muted mb-1">Número de Contenedor</p>
                <p class="fw-bold fs-5">{{ $contenedor->numero }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Placa del Vehículo</p>
                <p class="fw-bold">{{ $contenedor->placa_vehiculo ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Cliente</p>
                <p class="fw-bold">{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Estado</p>
                <span class="badge bg-{{ $contenedor->estado->color() }} fs-6">
                    {{ $contenedor->estado->label() }}
                </span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <p class="text-muted mb-1">Fecha de Ingreso</p>
                <p class="fw-bold">{{ $contenedor->fecha_ingreso ? $contenedor->fecha_ingreso->format('d/m/Y H:i') : 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Fecha de Salida</p>
                <p class="fw-bold">{{ $contenedor->fecha_salida ? $contenedor->fecha_salida->format('d/m/Y H:i') : 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Destino de Salida</p>
                <p class="fw-bold">{{ $contenedor->destino_salida ?? 'No registrado' }}</p>
            </div>
            <div class="col-md-3">
                <p class="text-muted mb-1">Limpieza</p>
                @if($contenedor->limpieza_registrada)
                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i> Registrada</span>
                @else
                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle me-1"></i> Pendiente</span>
                @endif
            </div>
        </div>
    </div>
</div>

@php
    $yaRegistroSalida = $contenedor->estado === \App\Enums\ContenedorEstado::FueraDePatio;
    $limpiezaRegistrada = $contenedor->limpieza_registrada && $contenedor->destino_salida;
@endphp

<!-- Step 1: Registrar Limpieza y Destino -->
@if(!$yaRegistroSalida)
<div class="card mb-4">
    <div class="card-header {{ $limpiezaRegistrada ? 'bg-success text-white' : 'bg-warning text-dark' }}">
        <h5 class="mb-0">
            @if($limpiezaRegistrada)
                <i class="bi bi-check-circle me-2"></i>Paso 1: Limpieza y Destino (Completado)
            @else
                <i class="bi bi-1-circle me-2"></i>Paso 1: Registrar Limpieza y Destino
            @endif
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('gate-out.limpieza', $contenedor) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="limpieza" class="form-label">Estado de Limpieza</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="limpieza"
                               name="limpieza" value="1"
                               {{ old('limpieza', $contenedor->limpieza_registrada) ? 'checked' : '' }}>
                        <label class="form-check-label" for="limpieza">
                            Contenedor limpio para salida
                        </label>
                    </div>
                </div>
                <div class="col-md-5">
                    <label for="destino" class="form-label">Destino de Salida <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('destino') is-invalid @enderror"
                           id="destino" name="destino"
                           value="{{ old('destino', $contenedor->destino_salida) }}"
                           placeholder="Ej: Puerto de Cartagena, Bodega del cliente..."
                           required>
                    @error('destino')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Registrar Limpieza
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Step 2: Registrar Salida -->
@if($limpiezaRegistrada && !$yaRegistroSalida)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-2-circle me-2"></i>Paso 2: Registrar Salida</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('gate-out.store', $contenedor) }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="notas" class="form-label">Notas u Observaciones</label>
                    <textarea class="form-control @error('notas') is-invalid @enderror"
                              id="notas" name="notas" rows="3"
                              placeholder="Observaciones de salida...">{{ old('notas') }}</textarea>
                    @error('notas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="fotos" class="form-label">Fotos de Salida</label>
                    <input type="file" class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                           id="fotos" name="fotos[]" multiple accept="image/*">
                    <div class="form-text">Máximo 10 fotos, 5MB cada una (JPG, PNG)</div>
                    @error('fotos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('fotos.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-lg"
                            onclick="return confirm('¿Está seguro de registrar la salida de este contenedor? Esta acción no se puede deshacer.')">
                        <i class="bi bi-box-arrow-right me-1"></i> Registrar Salida
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Download Tirilla (if already exited) -->
@if($yaRegistroSalida)
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Salida Registrada</h5>
    </div>
    <div class="card-body">
        <p class="mb-3">La salida de este contenedor fue registrada exitosamente.</p>
        <a href="{{ route('gate-out.tirilla', $contenedor) }}" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Tirilla de Soporte (PDF)
        </a>
    </div>
</div>

<!-- Salida Event Details -->
@php
    $gateOutEvent = $contenedor->gateEvents->where('tipo', \App\Enums\GateEventTipo::GateOut)->first();
@endphp
@if($gateOutEvent)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Detalles del Evento Salida</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p class="text-muted mb-1">Registrado por</p>
                <p class="fw-bold">{{ $gateOutEvent->usuario->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1">Hora de Registro</p>
                <p class="fw-bold">{{ $gateOutEvent->hora->format('d/m/Y H:i') }}</p>
            </div>
            <div class="col-md-4">
                <p class="text-muted mb-1">Notas</p>
                <p class="fw-bold">{{ $gateOutEvent->notas ?? 'Sin notas' }}</p>
            </div>
        </div>

        @if($gateOutEvent->photos->count() > 0)
        <hr>
        <h6><i class="bi bi-camera me-1"></i> Fotos de Salida</h6>
        <div class="row g-2 mt-2">
            @foreach($gateOutEvent->photos as $photo)
            <div class="col-md-3 col-6">
                <a href="{{ $photo->url }}" target="_blank">
                    <img src="{{ $photo->url }}" class="img-fluid rounded shadow-sm"
                         alt="{{ $photo->nombre }}">
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif
@endif
@endsection