@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-bar-chart me-2"></i>Inventario actual por cliente</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">Inventario por cliente</li>
        </ol>
    </nav>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="cliente_id" class="form-select">
                    <option value="">Todos los clientes</option>
                    @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(request('cliente_id') == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrar</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Cliente</th><th class="text-end">Referencias</th><th class="text-end">Unidades disponibles</th></tr></thead>
            <tbody>
                @forelse ($datos as $fila)
                <tr>
                    <td>{{ $fila['cliente'] }}</td>
                    <td class="text-end">{{ $fila['referencias'] }}</td>
                    <td class="text-end">{{ number_format($fila['unidades']) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted py-4">Sin inventario disponible.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
