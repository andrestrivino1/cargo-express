@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-archive me-2"></i>Almacenamiento e Inventario</h2>
    <a href="{{ route('inventario.ubicar') }}" class="btn btn-primary">
        <i class="bi bi-geo-alt me-1"></i> Asignar Ubicación
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventario.index') }}">
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
                    <label for="codigo" class="form-label">Código Referencia</label>
                    <input type="text" name="codigo" id="codigo" class="form-control"
                           value="{{ $filtros['codigo'] ?? '' }}" placeholder="Buscar código...">
                </div>
                <div class="col-md-2">
                    <label for="modulo" class="form-label">Módulo</label>
                    <select name="modulo" id="modulo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($modulos as $modulo)
                            <option value="{{ $modulo }}" {{ ($filtros['modulo'] ?? '') == $modulo ? 'selected' : '' }}>
                                {{ $modulo }}
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
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Botones de Exportación -->
<div class="mb-3">
    <a href="{{ route('inventario.export.excel', request()->query()) }}" class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar Excel
    </a>
    <a href="{{ route('inventario.export.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
    </a>
</div>

<!-- Tabla de Inventario -->
<div class="card">
    <div class="card-body">
        @if($referencias->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código Referencia</th>
                        <th>Contenedor</th>
                        <th>Cliente</th>
                        <th>Módulo</th>
                        <th>Posición</th>
                        <th>Cantidad Actual</th>
                        <th>Días Almacenamiento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($referencias as $ref)
                    <tr>
                        <td><strong>{{ $ref->codigo }}</strong></td>
                        <td>{{ $ref->contenedor->numero ?? 'N/A' }}</td>
                        <td>{{ $ref->cliente->name ?? 'N/A' }}</td>
                        <td>{{ $ref->ubicacionPatio->modulo ?? 'Sin asignar' }}</td>
                        <td>{{ $ref->ubicacionPatio->posicion ?? 'Sin asignar' }}</td>
                        <td>{{ $ref->cantidad_actual }}</td>
                        <td>
                            <span class="badge bg-{{ $ref->dias_almacenamiento > 30 ? 'warning' : 'info' }}">
                                {{ $ref->dias_almacenamiento }} días
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $referencias->withQueryString()->links() }}
        </div>
        @else
        <p class="text-muted mb-0">No se encontraron referencias en inventario.</p>
        @endif
    </div>
</div>
@endsection