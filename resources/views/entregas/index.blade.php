@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-truck me-2"></i>Entregas - Órdenes de Cargue</h2>
    <div>
        <a href="{{ route('entregas.export.excel', request()->query()) }}" class="btn btn-success me-2">
            <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
        </a>
        <a href="{{ route('entregas.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Orden
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('entregas.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                       value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                       value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ ($filtros['cliente_id'] ?? '') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach(\App\Enums\OrdenCargueEstado::cases() as $estado)
                        <option value="{{ $estado->value }}" {{ ($filtros['estado'] ?? '') == $estado->value ? 'selected' : '' }}>
                            {{ $estado->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body">
        @if($ordenes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Despachador</th>
                        <th>Fecha Despacho</th>
                        <th>Estado</th>
                        <th>Tarjas</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordenes as $orden)
                    <tr>
                        <td><strong>{{ $orden->id }}</strong></td>
                        <td>{{ $orden->cliente->name ?? 'N/A' }}</td>
                        <td>{{ $orden->despachador->name ?? 'Sin asignar' }}</td>
                        <td>{{ $orden->fecha_despacho->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $orden->estado->color() }}">
                                {{ $orden->estado->label() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $orden->tarjas->count() }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('entregas.show', $orden) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $ordenes->withQueryString()->links() }}
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox display-4"></i>
            <p class="mt-2">No se encontraron órdenes de cargue.</p>
        </div>
        @endif
    </div>
</div>
@endsection
