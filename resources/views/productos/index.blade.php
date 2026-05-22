@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box me-2"></i>Productos</h2>
    @can('inventario.ubicar')
    <a href="{{ route('productos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Producto
    </a>
    @endcan
</div>

{{-- Buscador --}}
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('productos.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="search" class="form-label">Buscar por nombre</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="{{ $search }}" placeholder="Escriba el nombre del producto...">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
                @if($search)
                <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card">
    <div class="card-body">
        @if($productos->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Medidas</th>
                        <th>Calibre</th>
                        <th>Peso (kg)</th>
                        <th>Empaque</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $producto)
                    <tr>
                        <td><strong>{{ $producto->nombre }}</strong></td>
                        <td>{{ $producto->medidas ?? '-' }}</td>
                        <td>{{ $producto->calibre ?? '-' }}</td>
                        <td>{{ $producto->peso ? number_format($producto->peso, 2) : '-' }}</td>
                        <td>{{ $producto->empaque ?? '-' }}</td>
                        <td>
                            @if($producto->activo)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('inventario.ubicar')
                            <a href="{{ route('productos.edit', $producto) }}"
                               class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('productos.destroy', $producto) }}" method="POST"
                                  class="d-inline" onsubmit="return confirm('{{ '¿Está seguro de eliminar este producto?' }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $productos->links() }}
        </div>
        @else
        <div class="text-center py-4">
            <i class="bi bi-box fs-1 text-muted"></i>
            <p class="text-muted mt-2">No se encontraron productos.</p>
        </div>
        @endif
    </div>
</div>
@endsection
