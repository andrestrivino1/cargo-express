@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle me-2"></i>Crear Orden de Cargue</h2>
    <a href="{{ route('entregas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('entregas.store') }}">
            @csrf

            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
                    <option value="">Seleccione un cliente</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
                @error('cliente_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="fecha_despacho" class="form-label">Fecha de Despacho <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('fecha_despacho') is-invalid @enderror"
                       id="fecha_despacho" name="fecha_despacho" value="{{ old('fecha_despacho') }}" required>
                @error('fecha_despacho')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notas" class="form-label">Notas</label>
                <textarea class="form-control @error('notas') is-invalid @enderror"
                          id="notas" name="notas" rows="3">{{ old('notas') }}</textarea>
                @error('notas')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Crear Orden de Cargue
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
