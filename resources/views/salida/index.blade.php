@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-right me-2"></i>Salida de Mercancía</h2>
    @can('salida.crear')
    <a href="{{ route('salida.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nueva Salida
    </a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ODC</th>
                    <th>Cliente</th>
                    <th>Conductor</th>
                    <th>Destino</th>
                    <th>Fecha salida</th>
                    <th>Despachador</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salidas as $tarja)
                <tr>
                    <td><span class="badge bg-dark">ODC-{{ $tarja->consecutivo_odc }}</span></td>
                    <td>{{ $tarja->ordenCargue?->cliente?->name ?? '—' }}</td>
                    <td>{{ $tarja->conductor }}</td>
                    <td>{{ $tarja->destino }}</td>
                    <td>{{ $tarja->fecha_entrega?->format('d/m/Y') }}</td>
                    <td>{{ $tarja->despachador?->name }}</td>
                    <td class="text-end">
                        <a href="{{ route('salida.show', $tarja) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('salida.orden-salida.pdf', $tarja) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay salidas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $salidas->withQueryString()->links() }}</div>
@endsection
