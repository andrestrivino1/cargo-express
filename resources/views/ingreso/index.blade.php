@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso de Mercancía</h2>
    @can('ingreso.crear')
    <a href="{{ route('ingreso.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Ingreso
    </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="bl" value="{{ request('bl') }}" class="form-control" placeholder="Buscar por BL">
            </div>
            <div class="col-md-4">
                <input type="text" name="numero" value="{{ request('numero') }}" class="form-control" placeholder="Buscar por contenedor">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Contenedor</th>
                    <th>BL</th>
                    <th>Tipo de mercancía</th>
                    <th>Cliente</th>
                    <th>Referencias</th>
                    <th>Fecha ingreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contenedores as $contenedor)
                <tr>
                    <td>{{ $contenedor->numero }}</td>
                    <td>{{ $contenedor->bl }}</td>
                    <td>{{ $contenedor->tipo_mercancia }}</td>
                    <td>{{ $contenedor->referencias->first()?->cliente?->name ?? '—' }}</td>
                    <td>{{ $contenedor->referencias->count() }}</td>
                    <td>{{ $contenedor->fecha_ingreso?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('ingreso.show', $contenedor) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay ingresos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $contenedores->withQueryString()->links() }}</div>
@endsection
