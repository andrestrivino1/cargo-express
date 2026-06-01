@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Referencia #{{ $referencia->id }}</h1>
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('inventario.update', $referencia) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('codigo') is-invalid @enderror"
                       id="codigo" name="codigo" value="{{ old('codigo', $referencia->codigo) }}" maxlength="100" required>
                @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                          id="descripcion" name="descripcion" rows="2">{{ old('descripcion', $referencia->descripcion) }}</textarea>
                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="unidad_medida" class="form-label">Unidad de medida</label>
                <input type="text" class="form-control @error('unidad_medida') is-invalid @enderror"
                       id="unidad_medida" name="unidad_medida" value="{{ old('unidad_medida', $referencia->unidad_medida) }}" maxlength="20">
                @error('unidad_medida') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="ubicacion_patio_id" class="form-label">Ubicación en patio</label>
                <select class="form-select @error('ubicacion_patio_id') is-invalid @enderror" id="ubicacion_patio_id" name="ubicacion_patio_id">
                    <option value="">-- Sin ubicación --</option>
                    @foreach($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" {{ (string) old('ubicacion_patio_id', $referencia->ubicacion_patio_id) === (string) $ubicacion->id ? 'selected' : '' }}>
                            {{ $ubicacion->modulo }} - {{ $ubicacion->posicion }}
                        </option>
                    @endforeach
                </select>
                @error('ubicacion_patio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="fecha_ingreso" class="form-label">Fecha de ingreso</label>
                <input type="datetime-local" class="form-control @error('fecha_ingreso') is-invalid @enderror"
                       id="fecha_ingreso" name="fecha_ingreso"
                       value="{{ old('fecha_ingreso', $referencia->fecha_ingreso?->format('Y-m-d\TH:i')) }}">
                @error('fecha_ingreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <p class="text-muted small">Las cantidades de inventario no se editan aquí; se ajustan mediante movimientos y transferencias.</p>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $referencia])
@endsection
