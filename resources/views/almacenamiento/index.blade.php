@extends('layouts.app')

@section('content')
@php $esCliente = $esCliente ?? false; @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-archive me-2"></i>{{ $esCliente ? 'Mi Inventario' : 'Almacenamiento e Inventario' }}</h2>
    @can('inventario.ubicar')
    <a href="{{ route('inventario.ubicar') }}" class="btn btn-primary">
        <i class="bi bi-geo-alt me-1"></i> Asignar Ubicación
    </a>
    @endcan
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventario.index') }}">
            <div class="row g-3">
                {{-- Un cliente no elige de qué cliente ver: el servicio fuerza el suyo. --}}
                @unless($esCliente)
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
                @endunless
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
                {{-- Feature 010: las referencias retiradas solo se ven pidiéndolas,
                     y solo las ve quien puede retirar. --}}
                @if($puedeRetirar ?? false)
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="incluir_retiradas" id="incluir_retiradas" value="1"
                               class="form-check-input" {{ !empty($filtros['incluir_retiradas']) ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="incluir_retiradas">
                            Incluir referencias retiradas del inventario
                        </label>
                    </div>
                </div>
                @endif
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
                        @role('administrador|coordinador')<th>Acciones</th>@endrole
                    </tr>
                </thead>
                <tbody>
                    @foreach($referencias as $ref)
                    <tr @if($ref->deleted_at) class="table-secondary" @endif>
                        <td>
                            <strong>{{ $ref->codigo }}</strong>
                            @if($ref->deleted_at)
                            <span class="badge bg-dark ms-1" title="Retirada del inventario">Retirada</span>
                            <div class="small text-muted">
                                Retirada por {{ $ref->retiradoPor->name ?? 'usuario eliminado' }}
                                el {{ $ref->deleted_at->format('d/m/Y H:i') }}
                            </div>
                            @endif
                        </td>
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
                        @role('administrador|coordinador')
                            <td class="text-nowrap">
                                @unless($ref->deleted_at)
                                <a href="{{ route('inventario.editar', $ref) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar referencia">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @can('inventario.retirar')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        title="Retirar del inventario"
                                        data-bs-toggle="modal" data-bs-target="#modalRetiro"
                                        data-ref-id="{{ $ref->id }}"
                                        data-ref-codigo="{{ $ref->codigo }}"
                                        data-ref-cliente="{{ $ref->cliente->name ?? 'N/A' }}">
                                    <i class="bi bi-box-arrow-right"></i>
                                </button>
                                @endcan
                                @else
                                <span class="text-muted small">—</span>
                                @endunless
                            </td>
                        @endrole
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $referencias->withQueryString()->links() }}
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox d-block fs-1 mb-2"></i>
            @if ($esCliente)
            <h5>Sin mercancía almacenada</h5>
            <p class="mb-0">Todavía no tienes mercancía registrada en el almacén.</p>
            @else
            <h5>Sin resultados</h5>
            <p class="mb-0">No se encontraron referencias en inventario.</p>
            @endif
        </div>
        @endif
    </div>
</div>

@can('inventario.retirar')
{{-- Feature 010 / US2 — confirmación del retiro. Un solo modal para toda la
     tabla: el botón de cada fila le pasa qué referencia se va a retirar. --}}
<div class="modal fade" id="modalRetiro" tabindex="-1" aria-labelledby="modalRetiroLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formRetiro">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRetiroLabel">
                        <i class="bi bi-box-arrow-right me-1"></i> Retirar del inventario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Va a retirar la referencia <strong id="retiroCodigo"></strong>
                        del cliente <strong id="retiroCliente"></strong>.
                    </p>
                    <p class="small text-muted mb-0">
                        Dejará de aparecer en el inventario, en los exportables y en la consulta del cliente,
                        y no podrá usarse en salidas ni transferencias nuevas.
                        Su historial se conserva.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Retirar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('modalRetiro')?.addEventListener('show.bs.modal', function (event) {
        const boton = event.relatedTarget;
        if (!boton) return;

        document.getElementById('retiroCodigo').textContent = boton.dataset.refCodigo;
        document.getElementById('retiroCliente').textContent = boton.dataset.refCliente;
        document.getElementById('formRetiro').action = '{{ url('inventario') }}/' + boton.dataset.refId;
    });
</script>
@endpush
@endcan
@endsection