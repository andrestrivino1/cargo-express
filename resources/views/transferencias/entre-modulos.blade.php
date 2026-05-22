@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-arrows-move me-2"></i>Transferencia entre Módulos</h2>
    <p class="text-muted">Mover productos de un módulo a otro para el mismo cliente.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('transferencias.entre-modulos.store') }}">
            @csrf

            <div class="mb-3">
                <label for="referencia_id" class="form-label">Referencia <span class="text-danger">*</span></label>
                <select name="referencia_id" id="referencia_id" class="form-select @error('referencia_id') is-invalid @enderror" required>
                    <option value="">Seleccione una referencia</option>
                    @foreach($referencias as $ref)
                        <option value="{{ $ref->id }}" {{ old('referencia_id') == $ref->id ? 'selected' : '' }}
                            data-ubicacion="{{ $ref->ubicacion_patio_id }}">
                            {{ $ref->codigo }} - {{ $ref->producto->nombre ?? 'Sin producto' }}
                            - {{ $ref->cliente->name ?? 'Sin cliente' }}
                            - {{ $ref->ubicacionPatio->modulo ?? '?' }}/{{ $ref->ubicacionPatio->posicion ?? '?' }}
                            - Disp: {{ $ref->cantidad_actual }}
                        </option>
                    @endforeach
                </select>
                @error('referencia_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="ubicacion_destino_id" class="form-label">Ubicación Destino <span class="text-danger">*</span></label>
                <select name="ubicacion_destino_id" id="ubicacion_destino_id" class="form-select @error('ubicacion_destino_id') is-invalid @enderror" required>
                    <option value="">Seleccione la ubicación destino</option>
                    @foreach($ubicaciones as $ub)
                        <option value="{{ $ub->id }}" {{ old('ubicacion_destino_id') == $ub->id ? 'selected' : '' }}>
                            {{ $ub->modulo }} / {{ $ub->posicion }} {{ $ub->descripcion ? '- ' . $ub->descripcion : '' }}
                        </option>
                    @endforeach
                </select>
                @error('ubicacion_destino_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                <input type="number" name="cantidad" id="cantidad" class="form-control @error('cantidad') is-invalid @enderror"
                       value="{{ old('cantidad') }}" min="1" required>
                @error('cantidad')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Realizar Transferencia
                </button>
                <a href="{{ route('transferencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
