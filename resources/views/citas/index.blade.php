@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-check me-2"></i>Citas de llegada</h2>
    @can('citas.crear')
    <a href="{{ route('citas.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nueva Cita
    </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <input type="date" name="fecha_esperada" value="{{ request('fecha_esperada') }}" class="form-control" title="Fecha esperada">
            </div>
            <div class="col-md-2">
                <input type="text" name="bl" value="{{ request('bl') }}" class="form-control" placeholder="BL">
            </div>
            <div class="col-md-2">
                <input type="text" name="numero_contenedor" value="{{ request('numero_contenedor') }}" class="form-control" placeholder="Contenedor">
            </div>
            <div class="col-md-2">
                <input type="text" name="placa" value="{{ request('placa') }}" class="form-control" placeholder="Placa">
            </div>
            <div class="col-md-2">
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $estado)
                    <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->label() }}</option>
                    @endforeach
                </select>
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
                    <th>Fecha esperada</th>
                    <th>Contenedor</th>
                    <th>BL</th>
                    <th>Condición</th>
                    <th>Conductor</th>
                    <th>Placa</th>
                    <th>Empresa</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($citas as $cita)
                @php $estadoEfectivo = $cita->estadoEfectivo(); @endphp
                <tr>
                    <td>{{ $cita->fecha_esperada?->format('d/m/Y') }}</td>
                    <td><code>{{ $cita->numero_contenedor }}</code></td>
                    <td>{{ $cita->ingreso?->bl ?? '—' }}</td>
                    <td><span class="badge bg-{{ $cita->condicion->color() }}">{{ $cita->condicion->label() }}</span></td>
                    <td>{{ $cita->conductor_nombre }}</td>
                    <td>{{ $cita->placa_original ?? $cita->placa }}</td>
                    <td>{{ $cita->empresa }}</td>
                    <td><span class="badge bg-{{ $estadoEfectivo->color() }}">{{ $estadoEfectivo->label() }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        @can('citas.editar')
                        @if ($cita->puedeEditarse())
                        <a href="{{ route('citas.editar', $cita) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                        No hay citas que coincidan con los filtros.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $citas->links() }}
</div>
@endsection
