@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-exclamation-triangle me-2"></i>Novedades</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">Novedades</li>
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
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Contenedor</th><th>Referencia</th><th>Descripción</th><th>Operador</th></tr></thead>
            <tbody>
                @forelse ($novedades as $nov)
                <tr>
                    <td>{{ $nov->created_at?->format('d/m/Y H:i') }}</td>
                    <td><span class="badge bg-{{ $nov->tipo->color() }}">{{ $nov->tipo->label() }}</span></td>
                    <td>{{ $nov->ordenVaciado?->contenedor?->numero }}</td>
                    <td>{{ $nov->referencia?->codigo }}</td>
                    <td>{{ $nov->descripcion }}</td>
                    <td>{{ $nov->operador?->name }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Sin novedades en el rango.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $novedades->withQueryString()->links() }}</div>
@endsection
