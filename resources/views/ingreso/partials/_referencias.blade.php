{{-- Referencias del BL agrupadas por contenedor (solo lectura). Feature 007 / US1. --}}
<div class="card mt-3">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-list-ul me-2"></i>
        <strong>Referencias del BL</strong>
        <span class="badge bg-secondary ms-2">{{ $ingreso->contenedores->sum(fn ($c) => $c->referencias->count()) }}</span>
    </div>
    <div class="card-body">
        @forelse ($ingreso->contenedores as $contenedor)
        <div class="mb-3">
            <h6 class="text-muted mb-2"><i class="bi bi-box-seam me-1"></i>Contenedor {{ $contenedor->numero }}</h6>
            @if ($contenedor->referencias->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Descripción / Producto</th>
                            <th class="text-end">Cantidad</th>
                            <th>Unidad</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contenedor->referencias as $referencia)
                        <tr>
                            <td>{{ $referencia->codigo }}</td>
                            <td>{{ $referencia->descripcion ?? optional($referencia->producto)->nombre }}</td>
                            <td class="text-end">{{ $referencia->cantidad_actual }} / {{ $referencia->cantidad_inicial }}</td>
                            <td>{{ $referencia->unidad_medida }}</td>
                            <td>{{ $referencia->ubicacionPatio ? $referencia->ubicacionPatio->modulo.' - '.$referencia->ubicacionPatio->posicion : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted small mb-0">Sin referencias en este contenedor.</p>
            @endif
        </div>
        @empty
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Sin referencias asociadas a este BL.</p>
        @endforelse
    </div>
</div>
