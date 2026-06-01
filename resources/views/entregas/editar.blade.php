@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Entrega #{{ $ordenCargue->id }}</h1>
    <a href="{{ route('entregas.show', $ordenCargue) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('entregas.update', $ordenCargue) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
                    <option value="">-- Seleccionar cliente --</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ (string) old('cliente_id', $ordenCargue->cliente_id) === (string) $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }} ({{ $cliente->email }})
                        </option>
                    @endforeach
                </select>
                @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="fecha_despacho" class="form-label">Fecha de despacho <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('fecha_despacho') is-invalid @enderror"
                       id="fecha_despacho" name="fecha_despacho"
                       value="{{ old('fecha_despacho', $ordenCargue->fecha_despacho?->format('Y-m-d')) }}" required>
                @error('fecha_despacho') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="notas" class="form-label">Notas</label>
                <textarea class="form-control @error('notas') is-invalid @enderror"
                          id="notas" name="notas" rows="3">{{ old('notas', $ordenCargue->notas) }}</textarea>
                @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('entregas.show', $ordenCargue) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $ordenCargue])
@endsection
