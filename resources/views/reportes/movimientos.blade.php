@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-arrow-left-right me-2"></i>{{ $titulo }}</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">{{ $titulo }}</li>
        </ol>
    </nav>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrar</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Fecha</th><th>Tipo</th><th>Cliente</th><th>Contenedor</th>
                    <th>Referencia</th><th class="text-end">Cantidad</th><th class="text-end">Saldo</th><th>Responsable</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $mov)
                <tr>
                    <td>
                        @if (($usarFechaIngreso ?? false) && $mov->referencia)
                            {{ $mov->referencia->fecha_ingreso?->format('d/m/Y') }}
                        @else
                            {{ $mov->created_at?->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td><span class="badge bg-{{ $mov->tipo->color() }}">{{ $mov->tipo->label() }}</span></td>
                    <td>{{ $mov->referencia?->cliente?->name }}</td>
                    <td>{{ $mov->referencia?->contenedor?->numero }}</td>
                    <td>{{ $mov->referencia?->codigo }}</td>
                    <td class="text-end">{{ number_format($mov->cantidad) }}</td>
                    <td class="text-end">{{ number_format($mov->saldo_resultante) }}</td>
                    <td>{{ $mov->usuario?->name }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Sin movimientos en el rango.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $movimientos->withQueryString()->links() }}</div>
@endsection
