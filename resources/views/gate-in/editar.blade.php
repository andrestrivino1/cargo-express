@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Ingreso #{{ $gateEvent->id }}</h1>
    <a href="{{ route('gate-in.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('gate-in.update', $gateEvent) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="hora" class="form-label">Fecha y hora de ingreso <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control @error('hora') is-invalid @enderror"
                       id="hora" name="hora"
                       value="{{ old('hora', $gateEvent->hora?->format('Y-m-d\TH:i')) }}" required>
                @error('hora') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="estado_fisico" class="form-label">Estado físico</label>
                <textarea class="form-control @error('estado_fisico') is-invalid @enderror"
                          id="estado_fisico" name="estado_fisico" rows="2">{{ old('estado_fisico', $gateEvent->estado_fisico) }}</textarea>
                @error('estado_fisico') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="notas" class="form-label">Notas</label>
                <textarea class="form-control @error('notas') is-invalid @enderror"
                          id="notas" name="notas" rows="3">{{ old('notas', $gateEvent->notas) }}</textarea>
                @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('gate-in.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $gateEvent])
@endsection
