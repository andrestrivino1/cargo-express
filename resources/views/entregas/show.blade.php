@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck me-2"></i>Orden de Cargue #{{ $ordenCargue->id }}</h2>
    <a href="{{ route('entregas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<!-- Detalle de la orden -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle me-1"></i> Detalle de la Orden</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Cliente:</strong>
                <p>{{ $ordenCargue->cliente->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Despachador:</strong>
                <p>{{ $ordenCargue->despachador->name ?? 'Sin asignar' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Fecha Despacho:</strong>
                <p>{{ $ordenCargue->fecha_despacho->format('d/m/Y') }}</p>
            </div>
            <div class="col-md-3">
                <strong>Estado:</strong>
                <p>
                    <span class="badge bg-{{ $ordenCargue->estado->color() }}">
                        {{ $ordenCargue->estado->label() }}
                    </span>
                </p>
            </div>
        </div>
        @if($ordenCargue->notas)
        <div class="row">
            <div class="col-12">
                <strong>Notas:</strong>
                <p>{{ $ordenCargue->notas }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Tarjas existentes -->
@if($ordenCargue->tarjas->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clipboard-check me-1"></i> Tarjas Generadas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarja #</th>
                        <th>Despachador</th>
                        <th>Fecha Entrega</th>
                        <th>Total Unidades</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordenCargue->tarjas as $tarja)
                    <tr>
                        <td><strong>{{ $tarja->id }}</strong></td>
                        <td>{{ $tarja->despachador->name ?? 'N/A' }}</td>
                        <td>{{ $tarja->fecha_entrega->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($tarja->detalles->sum('cantidad_entregada')) }}</td>
                        <td class="text-center">
                            <a href="{{ route('entregas.tarja.show', $tarja) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('entregas.tarja.pdf', $tarja) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Generar nueva tarja -->
@if($ordenCargue->estado->value !== 'completada' && $ordenCargue->estado->value !== 'cancelada' && $referencias->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-plus-circle me-1"></i> Generar Tarja</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('entregas.tarja.store', $ordenCargue) }}" id="tarjaForm">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>Referencia</th>
                            <th>Descripcion</th>
                            <th>Stock Actual</th>
                            <th>Ubicacion</th>
                            <th>Cantidad a Entregar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($referencias as $index => $referencia)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input ref-check" type="checkbox"
                                           data-index="{{ $index }}" id="check_{{ $index }}">
                                </div>
                            </td>
                            <td><strong>{{ $referencia->codigo }}</strong></td>
                            <td>{{ $referencia->descripcion }}</td>
                            <td>
                                <span class="badge bg-info">{{ number_format($referencia->cantidad_actual) }}</span>
                            </td>
                            <td>
                                {{ $referencia->ubicacionPatio->modulo ?? 'N/A' }} - {{ $referencia->ubicacionPatio->posicion ?? 'N/A' }}
                            </td>
                            <td style="width: 180px;">
                                <input type="hidden" class="ref-id" name="detalles[{{ $index }}][referencia_id]"
                                       value="{{ $referencia->id }}" disabled>
                                <input type="hidden" class="ref-ubicacion" name="detalles[{{ $index }}][ubicacion_origen_id]"
                                       value="{{ $referencia->ubicacion_patio_id }}" disabled>
                                <input type="number" class="form-control form-control-sm ref-cantidad"
                                       name="detalles[{{ $index }}][cantidad_entregada]"
                                       min="1" max="{{ $referencia->cantidad_actual }}"
                                       placeholder="0" disabled>
                                @error("detalles.{$index}.cantidad_entregada")
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" id="btnGenerarTarja">
                    <i class="bi bi-check-lg me-1"></i> Generar Tarja
                </button>
            </div>
        </form>
    </div>
</div>
@elseif($referencias->count() === 0 && $ordenCargue->estado->value !== 'completada')
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    No hay referencias con stock disponible para este cliente.
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.ref-check');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                toggleRow(cb);
            });
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            toggleRow(cb);
        });
    });

    function toggleRow(checkbox) {
        const index = checkbox.dataset.index;
        const row = checkbox.closest('tr');
        const inputs = row.querySelectorAll('.ref-id, .ref-ubicacion, .ref-cantidad');

        inputs.forEach(input => {
            input.disabled = !checkbox.checked;
        });

        if (!checkbox.checked) {
            row.querySelector('.ref-cantidad').value = '';
        }
    }

    // Before submit, remove disabled fields that are unchecked
    document.getElementById('tarjaForm')?.addEventListener('submit', function (e) {
        const unchecked = document.querySelectorAll('.ref-check:not(:checked)');
        unchecked.forEach(cb => {
            const index = cb.dataset.index;
            const row = cb.closest('tr');
            row.querySelectorAll('input[name]').forEach(input => input.removeAttribute('name'));
        });

        // Re-index the detalles to be sequential
        const checked = document.querySelectorAll('.ref-check:checked');
        let i = 0;
        checked.forEach(cb => {
            const row = cb.closest('tr');
            row.querySelector('.ref-id').name = `detalles[${i}][referencia_id]`;
            row.querySelector('.ref-ubicacion').name = `detalles[${i}][ubicacion_origen_id]`;
            row.querySelector('.ref-cantidad').name = `detalles[${i}][cantidad_entregada]`;
            i++;
        });

        if (i === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una referencia.');
        }
    });
});
</script>
@endpush
