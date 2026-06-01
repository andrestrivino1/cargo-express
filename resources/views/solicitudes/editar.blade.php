@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Solicitud #{{ $solicitud->id }}</h1>
    <a href="{{ route('solicitudes.show', $solicitud) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('solicitudes.update', $solicitud) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
                    <option value="">-- Seleccionar cliente --</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ (string) old('cliente_id', $solicitud->cliente_id) === (string) $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }} ({{ $cliente->email }})
                        </option>
                    @endforeach
                </select>
                @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="numero_contenedor" class="form-label">Numero de Contenedor <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('numero_contenedor') is-invalid @enderror"
                       id="numero_contenedor" name="numero_contenedor"
                       value="{{ old('numero_contenedor', $solicitud->numero_contenedor) }}" maxlength="20" required>
                @error('numero_contenedor') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="fecha_solicitud" class="form-label">Fecha de la Solicitud <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('fecha_solicitud') is-invalid @enderror"
                       id="fecha_solicitud" name="fecha_solicitud"
                       value="{{ old('fecha_solicitud', $solicitud->fecha_solicitud->format('Y-m-d')) }}" required>
                @error('fecha_solicitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="naviera" class="form-label">Naviera</label>
                <input type="text" class="form-control @error('naviera') is-invalid @enderror"
                       id="naviera" name="naviera" value="{{ old('naviera', $solicitud->naviera) }}" maxlength="100">
                @error('naviera') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="puerto_origen" class="form-label">Puerto de Origen</label>
                <input type="text" class="form-control @error('puerto_origen') is-invalid @enderror"
                       id="puerto_origen" name="puerto_origen" value="{{ old('puerto_origen', $solicitud->puerto_origen) }}" maxlength="100">
                @error('puerto_origen') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripcion</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                          id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $solicitud->descripcion) }}</textarea>
                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('solicitudes.show', $solicitud) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $solicitud])
@endsection
