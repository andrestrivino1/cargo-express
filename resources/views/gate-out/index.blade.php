@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-right me-2"></i>Salida</h2>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="gateOutTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="listos-tab" data-bs-toggle="tab" data-bs-target="#listos"
                type="button" role="tab" aria-controls="listos" aria-selected="true">
            <i class="bi bi-check-circle me-1"></i> Listos para Salida
            <span class="badge bg-primary ms-1">{{ $listosParaSalida->total() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial"
                type="button" role="tab" aria-controls="historial" aria-selected="false">
            <i class="bi bi-clock-history me-1"></i> Historial de Salidas
            <span class="badge bg-secondary ms-1">{{ $historialSalidas->total() }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="gateOutTabsContent">
    <!-- Tab 1: Listos para Salida -->
    <div class="tab-pane fade show active" id="listos" role="tabpanel" aria-labelledby="listos-tab">
        <div class="card">
            <div class="card-body">
                @if($listosParaSalida->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Contenedor</th>
                                <th>Placa</th>
                                <th>Cliente</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                                <th>Limpieza</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($listosParaSalida as $contenedor)
                            <tr>
                                <td><strong>{{ $contenedor->numero }}</strong></td>
                                <td>{{ $contenedor->placa_vehiculo ?? 'N/A' }}</td>
                                <td>{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
                                <td>{{ $contenedor->fecha_ingreso ? $contenedor->fecha_ingreso->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $contenedor->estado->color() }}">
                                        {{ $contenedor->estado->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if($contenedor->limpieza_registrada)
                                        <span class="badge bg-success"><i class="bi bi-check-lg"></i> Registrada</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('gate-out.show', $contenedor) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Ver / Procesar
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $listosParaSalida->withQueryString()->links() }}
                </div>
                @else
                <p class="text-muted mb-0">No hay contenedores listos para salida.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Tab 2: Historial de Salidas -->
    <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('gate-out.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="form-select">
                                <option value="">Todos los clientes</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ ($filtros['cliente_id'] ?? '') == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_desde" class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                                   value="{{ $filtros['fecha_desde'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_hasta" class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                                   value="{{ $filtros['fecha_hasta'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="destino" class="form-label">Destino</label>
                            <input type="text" name="destino" id="destino" class="form-control"
                                   value="{{ $filtros['destino'] ?? '' }}" placeholder="Buscar destino...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('gate-out.export.excel', request()->query()) }}" class="btn btn-success">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla Historial -->
        <div class="card">
            <div class="card-body">
                @if($historialSalidas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Contenedor</th>
                                <th>Placa</th>
                                <th>Cliente</th>
                                <th>Fecha Ingreso</th>
                                <th>Fecha Salida</th>
                                <th>Destino</th>
                                <th>Limpieza</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historialSalidas as $contenedor)
                            <tr>
                                <td><strong>{{ $contenedor->numero }}</strong></td>
                                <td>{{ $contenedor->placa_vehiculo ?? 'N/A' }}</td>
                                <td>{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
                                <td>{{ $contenedor->fecha_ingreso ? $contenedor->fecha_ingreso->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ $contenedor->fecha_salida ? $contenedor->fecha_salida->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ $contenedor->destino_salida ?? 'N/A' }}</td>
                                <td>
                                    @if($contenedor->limpieza_registrada)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('gate-out.show', $contenedor) }}" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('gate-out.tirilla', $contenedor) }}" class="btn btn-outline-danger" title="Descargar Tirilla PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $historialSalidas->withQueryString()->links() }}
                </div>
                @else
                <p class="text-muted mb-0">No se encontraron salidas registradas.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Activate historial tab if filters are applied
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('fecha_desde') || urlParams.has('fecha_hasta') || urlParams.has('cliente_id') || urlParams.has('destino')) {
            const historialTab = document.getElementById('historial-tab');
            const tab = new bootstrap.Tab(historialTab);
            tab.show();
        }
    });
</script>
@endpush
@endsection