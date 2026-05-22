@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-geo-alt me-2"></i>Asignar Ubicación</h2>
    <p class="text-muted">Asigne una ubicación en el patio a una referencia sin ubicación.</p>
</div>

<div class="card">
    <div class="card-body">
        @if($referencias->count() > 0)
        <form method="POST" action="{{ route('inventario.asignar-ubicacion') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="referencia_id" class="form-label">Referencia</label>
                    <select name="referencia_id" id="referencia_id" class="form-select" required>
                        <option value="">Seleccione una referencia...</option>
                        @foreach($referencias as $ref)
                            <option value="{{ $ref->id }}" {{ old('referencia_id') == $ref->id ? 'selected' : '' }}>
                                {{ $ref->codigo }} - Contenedor: {{ $ref->contenedor->numero ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    @error('referencia_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="modulo_filter" class="form-label">Módulo</label>
                    <select id="modulo_filter" class="form-select">
                        <option value="">Todos los módulos</option>
                        @foreach($ubicaciones->pluck('modulo')->unique()->sort() as $modulo)
                            <option value="{{ $modulo }}">{{ $modulo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="ubicacion_patio_id" class="form-label">Posición</label>
                    <select name="ubicacion_patio_id" id="ubicacion_patio_id" class="form-select" required>
                        <option value="">Seleccione una posición...</option>
                        @foreach($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}"
                                    data-modulo="{{ $ubicacion->modulo }}"
                                    {{ old('ubicacion_patio_id') == $ubicacion->id ? 'selected' : '' }}>
                                {{ $ubicacion->modulo }} - {{ $ubicacion->posicion }}
                            </option>
                        @endforeach
                    </select>
                    @error('ubicacion_patio_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Asignar Ubicación
                </button>
                <a href="{{ route('inventario.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </form>
        @else
        <div class="text-center text-muted py-4">
            <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
            <p>Todas las referencias tienen ubicación asignada.</p>
            <a href="{{ route('inventario.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al Inventario
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const moduloFilter = document.getElementById('modulo_filter');
        const ubicacionSelect = document.getElementById('ubicacion_patio_id');

        if (moduloFilter && ubicacionSelect) {
            moduloFilter.addEventListener('change', function () {
                const selectedModulo = this.value;
                const options = ubicacionSelect.querySelectorAll('option[data-modulo]');

                options.forEach(function (option) {
                    if (!selectedModulo || option.dataset.modulo === selectedModulo) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });

                ubicacionSelect.value = '';
            });
        }
    });
</script>
@endpush