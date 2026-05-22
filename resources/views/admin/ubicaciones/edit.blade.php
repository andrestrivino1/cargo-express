@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil-square me-2"></i>Editar Ubicación</h2>
    <a href="{{ route('admin.ubicaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.ubicaciones.update', $ubicacion) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="modulo" class="form-label">Módulo <span class="text-danger">*</span></label>
                <input type="text" name="modulo" id="modulo" class="form-control @error('modulo') is-invalid @enderror"
                       value="{{ old('modulo', $ubicacion->modulo) }}" maxlength="50" required>
                @error('modulo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="posicion" class="form-label">Posición <span class="text-danger">*</span></label>
                <input type="text" name="posicion" id="posicion" class="form-control @error('posicion') is-invalid @enderror"
                       value="{{ old('posicion', $ubicacion->posicion) }}" maxlength="50" required>
                @error('posicion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $ubicacion->descripcion) }}</textarea>
                @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Actualizar
                </button>
                <a href="{{ route('admin.ubicaciones.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
