@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="h3">Asignar Orden de Servicio</h1>
    <p class="text-muted">Solicitud #{{ $solicitud->id }} - Contenedor: {{ $solicitud->numero_contenedor }}</p>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('solicitudes.orden-servicio.store', $solicitud) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="vehiculo" class="form-label">Vehiculo <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('vehiculo') is-invalid @enderror"
                       id="vehiculo" name="vehiculo" value="{{ old('vehiculo') }}"
                       maxlength="20" required>
                @error('vehiculo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="conductor" class="form-label">Conductor <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('conductor') is-invalid @enderror"
                       id="conductor" name="conductor" value="{{ old('conductor') }}"
                       maxlength="255" required>
                @error('conductor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="conductor_documento" class="form-label">Cedula del Conductor</label>
                <input type="text" class="form-control @error('conductor_documento') is-invalid @enderror"
                       id="conductor_documento" name="conductor_documento" value="{{ old('conductor_documento') }}"
                       maxlength="20">
                @error('conductor_documento')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="cita_puerto" class="form-label">Cita en Puerto <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control @error('cita_puerto') is-invalid @enderror"
                       id="cita_puerto" name="cita_puerto" value="{{ old('cita_puerto') }}" required>
                @error('cita_puerto')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Asignar Orden
                </button>
                <a href="{{ route('solicitudes.show', $solicitud) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection