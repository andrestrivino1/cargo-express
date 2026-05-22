@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-clipboard-data fs-4 me-2 text-primary"></i>
            <h2 class="mb-0">Reporte de Operación</h2>
        </div>
        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('reportes.operacion') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="cliente_id" class="form-label fw-semibold">Cliente</label>
                        <select class="form-select" id="cliente_id" name="cliente_id">
                            <option value="">-- Todos los clientes --</option>
                            @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ ($filtros['cliente_id'] ?? '') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_desde" class="form-label fw-semibold">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                               value="{{ $filtros['fecha_desde'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_hasta" class="form-label fw-semibold">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                               value="{{ $filtros['fecha_hasta'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($datos)
    {{-- Export Buttons --}}
    <div class="d-flex gap-2 mb-4">
        <form action="{{ route('reportes.export') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="formato" value="excel">
            <input type="hidden" name="cliente_id" value="{{ $filtros['cliente_id'] ?? '' }}">
            <input type="hidden" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
            <input type="hidden" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
            </button>
        </form>
        <form action="{{ route('reportes.export') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="formato" value="pdf">
            <input type="hidden" name="cliente_id" value="{{ $filtros['cliente_id'] ?? '' }}">
            <input type="hidden" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
            <input type="hidden" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
            </button>
        </form>
    </div>

    {{-- Movimientos Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-arrow-left-right me-2"></i>Movimientos (Gate Events)
                <span class="badge bg-light text-dark ms-2">{{ $datos['movimientos']->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Contenedor</th>
                            <th>Cliente</th>
                            <th>Usuario</th>
                            <th>Estado Físico</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datos['movimientos'] as $mov)
                        <tr>
                            <td>{{ $mov->hora?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $mov->tipo?->color() ?? 'secondary' }}">
                                    {{ $mov->tipo?->label() ?? $mov->tipo }}
                                </span>
                            </td>
                            <td>{{ $mov->contenedor?->numero ?? 'N/A' }}</td>
                            <td>{{ $mov->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A' }}</td>
                            <td>{{ $mov->usuario?->name ?? 'N/A' }}</td>
                            <td>{{ $mov->estado_fisico ?? 'N/A' }}</td>
                            <td>{{ Str::limit($mov->notas, 50) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No se encontraron movimientos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Novedades Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>Novedades
                <span class="badge bg-light text-dark ms-2">{{ $datos['novedades']->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Contenedor</th>
                            <th>Cliente</th>
                            <th>Referencia</th>
                            <th>Operador</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datos['novedades'] as $nov)
                        <tr>
                            <td>{{ $nov->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $nov->tipo?->color() ?? 'secondary' }}">
                                    {{ $nov->tipo?->label() ?? $nov->tipo }}
                                </span>
                            </td>
                            <td>{{ $nov->ordenVaciado?->contenedor?->numero ?? 'N/A' }}</td>
                            <td>{{ $nov->ordenVaciado?->contenedor?->ordenServicio?->solicitud?->cliente?->name ?? 'N/A' }}</td>
                            <td>{{ $nov->referencia?->codigo ?? 'N/A' }}</td>
                            <td>{{ $nov->operador?->name ?? 'N/A' }}</td>
                            <td>{{ Str::limit($nov->descripcion, 50) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No se encontraron novedades.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Resumen Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-bar-chart me-2"></i>Resumen - Días de Almacenamiento por Cliente
                <span class="badge bg-dark ms-2">{{ $datos['resumen']->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Total Referencias</th>
                            <th>Promedio Días Almacenamiento</th>
                            <th>Total Días Almacenamiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datos['resumen'] as $item)
                        <tr>
                            <td>{{ $item['cliente_nombre'] }}</td>
                            <td>{{ $item['total_referencias'] }}</td>
                            <td>{{ $item['promedio_dias'] }}</td>
                            <td>{{ $item['total_dias'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No se encontraron datos de almacenamiento.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-funnel display-1 d-block mb-3"></i>
            <h5>Aplique los filtros para generar el reporte</h5>
            <p class="mb-0">Seleccione un cliente y/o rango de fechas para consultar los datos de operación.</p>
        </div>
    </div>
    @endif
</div>
@endsection
