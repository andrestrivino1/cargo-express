@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-people-fill me-2"></i>Transferencia entre Clientes</h2>
    <p class="text-muted">Transferir productos de un cliente a otro. Requiere autorización verbal.</p>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Importante:</strong> Esta acción requiere autorización verbal del cliente origen. Se generará una constancia.
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('transferencias.entre-clientes.store') }}">
            @csrf

            <div class="mb-3">
                <label for="referencia_id" class="form-label">Referencia <span class="text-danger">*</span></label>
                <select name="referencia_id" id="referencia_id" class="form-select @error('referencia_id') is-invalid @enderror" required>
                    <option value="">Seleccione una referencia</option>
                    @foreach($referencias as $ref)
                        <option value="{{ $ref->id }}" {{ old('referencia_id') == $ref->id ? 'selected' : '' }}
                            data-cliente="{{ $ref->cliente_id }}">
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
                <label for="cliente_destino_id" class="form-label">Cliente Destino <span class="text-danger">*</span></label>
                <select name="cliente_destino_id" id="cliente_destino_id" class="form-select @error('cliente_destino_id') is-invalid @enderror" required>
                    <option value="">Seleccione el cliente destino</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_destino_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
                @error('cliente_destino_id')
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

            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
                <textarea name="motivo" id="motivo" class="form-control @error('motivo') is-invalid @enderror"
                          rows="3" required>{{ old('motivo') }}</textarea>
                @error('motivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="autorizacion_cliente" class="form-label">Autorización del Cliente <span class="text-danger">*</span></label>
                <input type="text" name="autorizacion_cliente" id="autorizacion_cliente"
                       class="form-control @error('autorizacion_cliente') is-invalid @enderror"
                       value="{{ old('autorizacion_cliente') }}"
                       placeholder="Nombre de quien autoriza y fecha"
                       maxlength="255" required>
                @error('autorizacion_cliente')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-check-lg me-1"></i> Realizar Transferencia
                </button>
                <a href="{{ route('transferencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
