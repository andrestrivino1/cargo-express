@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-left-right me-2"></i>Transferencias</h2>
    <div>
        <a href="{{ route('transferencias.entre-modulos.create') }}" class="btn btn-primary">
            <i class="bi bi-arrows-move me-1"></i> Transferir entre Módulos
        </a>
        <a href="{{ route('transferencias.entre-clientes.create') }}" class="btn btn-warning">
            <i class="bi bi-people-fill me-1"></i> Transferir entre Clientes
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('transferencias.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select name="tipo" id="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="entre_modulos" {{ ($filtros['tipo'] ?? '') === 'entre_modulos' ? 'selected' : '' }}>Entre Módulos</option>
                    <option value="entre_clientes" {{ ($filtros['tipo'] ?? '') === 'entre_clientes' ? 'selected' : '' }}>Entre Clientes</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select name="cliente_id" id="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ ($filtros['cliente_id'] ?? '') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Cliente Origen</th>
                    <th>Cliente Destino</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transferencias as $t)
                <tr>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($t->tipo === 'entre_modulos')
                            <span class="badge bg-primary">Entre Módulos</span>
                        @else
                            <span class="badge bg-warning text-dark">Entre Clientes</span>
                        @endif
                    </td>
                    <td>{{ $t->referenciaOrigen->producto->nombre ?? '-' }}</td>
                    <td>{{ $t->cantidad }}</td>
                    <td>{{ $t->ubicacionOrigen->modulo ?? '-' }} / {{ $t->ubicacionOrigen->posicion ?? '-' }}</td>
                    <td>{{ $t->ubicacionDestino->modulo ?? '-' }} / {{ $t->ubicacionDestino->posicion ?? '-' }}</td>
                    <td>{{ $t->clienteOrigen->name ?? '-' }}</td>
                    <td>{{ $t->clienteDestino->name ?? '-' }}</td>
                    <td>{{ $t->usuario->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('transferencias.show', $t) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if($t->tipo === 'entre_clientes')
                            <a href="{{ route('transferencias.constancia', $t) }}" class="btn btn-sm btn-outline-danger" title="Descargar constancia PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No se encontraron transferencias.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $transferencias->withQueryString()->links() }}
</div>
@endsection
