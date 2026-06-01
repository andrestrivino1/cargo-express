@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Transferencia #{{ $transferencia->id }}</h1>
    <a href="{{ route('transferencias.show', $transferencia) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="alert alert-info">
    Solo se pueden corregir datos descriptivos. Las cantidades y el movimiento de la transferencia no se modifican.
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('transferencias.update', $transferencia) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo</label>
                <textarea class="form-control @error('motivo') is-invalid @enderror"
                          id="motivo" name="motivo" rows="3">{{ old('motivo', $transferencia->motivo) }}</textarea>
                @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="autorizacion_cliente" class="form-label">Autorización del cliente</label>
                <input type="text" class="form-control @error('autorizacion_cliente') is-invalid @enderror"
                       id="autorizacion_cliente" name="autorizacion_cliente"
                       value="{{ old('autorizacion_cliente', $transferencia->autorizacion_cliente) }}" maxlength="255">
                @error('autorizacion_cliente') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('transferencias.show', $transferencia) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $transferencia])
@endsection
