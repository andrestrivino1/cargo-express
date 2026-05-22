@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Ordenes de Vaciado</h2>
    <a href="{{ route('vaciado.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nueva Orden de Vaciado
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($ordenes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Contenedor</th>
                        <th>Supervisor</th>
                        <th>Fecha Programada</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ordenes as $orden)
                    <tr>
                        <td>{{ $orden->id }}</td>
                        <td><strong>{{ $orden->contenedor->numero }}</strong></td>
                        <td>{{ $orden->supervisor->name }}</td>
                        <td>{{ $orden->fecha_programada->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $orden->estado->color() }}">
                                {{ $orden->estado->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('vaciado.show', $orden) }}"
                               class="btn btn-sm btn-outline-primary" title="Ver Detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $ordenes->links() }}
        </div>
        @else
        <p class="text-muted mb-0">No hay ordenes de vaciado registradas.</p>
        @endif
    </div>
</div>
@endsection