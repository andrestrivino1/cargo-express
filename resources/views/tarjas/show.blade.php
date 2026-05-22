@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check me-2"></i>Tarja #{{ $tarja->id }}</h2>
    <div>
        <a href="{{ route('entregas.tarja.pdf', $tarja) }}" class="btn btn-danger me-2">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF
        </a>
        <a href="{{ route('entregas.show', $tarja->ordenCargue) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Orden
        </a>
    </div>
</div>

<!-- Info de la tarja -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle me-1"></i> Informacion de la Tarja</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Orden de Cargue:</strong>
                <p>#{{ $tarja->ordenCargue->id }}</p>
            </div>
            <div class="col-md-3">
                <strong>Cliente:</strong>
                <p>{{ $tarja->ordenCargue->cliente->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Despachador:</strong>
                <p>{{ $tarja->despachador->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Fecha de Entrega:</strong>
                <p>{{ $tarja->fecha_entrega->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @if($tarja->observaciones)
        <div class="row">
            <div class="col-12">
                <strong>Observaciones:</strong>
                <p>{{ $tarja->observaciones }}</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Detalles de la tarja -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-ul me-1"></i> Detalles de Entrega</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Referencia</th>
                        <th>Descripcion</th>
                        <th class="text-center">Cantidad Entregada</th>
                        <th>Ubicacion Origen</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalUnidades = 0; @endphp
                    @foreach($tarja->detalles as $detalle)
                    @php $totalUnidades += $detalle->cantidad_entregada; @endphp
                    <tr>
                        <td><strong>{{ $detalle->referencia->codigo }}</strong></td>
                        <td>{{ $detalle->referencia->descripcion }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ number_format($detalle->cantidad_entregada) }}</span>
                        </td>
                        <td>{{ $detalle->ubicacionOrigen->modulo ?? 'N/A' }} - {{ $detalle->ubicacionOrigen->posicion ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="2" class="text-end"><strong>Total Unidades:</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalUnidades) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
