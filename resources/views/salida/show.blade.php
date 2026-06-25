@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-box-arrow-right me-2"></i>Salida ODC-{{ $tarja->consecutivo_odc }}</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('salida.index') }}">Salida</a></li>
                <li class="breadcrumb-item active">ODC-{{ $tarja->consecutivo_odc }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('salida.orden-salida.pdf', $tarja) }}" target="_blank" class="btn btn-dark">
        <i class="bi bi-file-earmark-pdf me-1"></i> Orden de Salida (PDF)
    </a>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Datos de la salida</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Cliente:</strong> {{ $tarja->ordenCargue?->cliente?->name }}</li>
                <li class="list-group-item"><strong>NIT:</strong> {{ $tarja->ordenCargue?->cliente?->nit ?? '—' }}</li>
                <li class="list-group-item"><strong>Fecha de salida:</strong> {{ $tarja->fecha_entrega?->format('d/m/Y') }}</li>
                <li class="list-group-item"><strong>Conductor:</strong> {{ $tarja->conductor }} ({{ $tarja->conductor_cedula ?? 's/cédula' }})</li>
                <li class="list-group-item"><strong>Placa:</strong> {{ $tarja->vehiculo }}</li>
                <li class="list-group-item"><strong>Transportador:</strong> {{ $tarja->transportador }}</li>
                <li class="list-group-item"><strong>Destino:</strong> {{ $tarja->destino }}</li>
                <li class="list-group-item"><strong>Despachador:</strong> {{ $tarja->despachador?->name }}</li>
                @if ($tarja->observaciones)
                <li class="list-group-item"><strong>Observaciones:</strong> {{ $tarja->observaciones }}</li>
                @endif
            </ul>
        </div>

        <div class="card mb-3">
            <div class="card-header">Evidencias</div>
            <div class="card-body row g-2">
                @foreach ($tarja->photos as $foto)
                <div class="col-6">
                    <div class="small text-muted">{{ \App\Enums\DocumentoCategoria::tryFrom($foto->categoria)?->label() }}</div>
                    <a href="{{ $foto->url }}" target="_blank"><img src="{{ $foto->url }}" class="img-fluid rounded border"></a>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Detalle de la carga</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Contenedor</th><th>Descripción</th><th class="text-end">Cantidad</th></tr></thead>
                    <tbody>
                        @foreach ($tarja->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->referencia?->contenedor?->numero }}</td>
                            <td>{{ $detalle->referencia?->descripcion ?? $detalle->referencia?->codigo }}</td>
                            <td class="text-end">{{ $detalle->cantidad_entregada }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="text-end">Total</th><th class="text-end">{{ $tarja->detalles->sum('cantidad_entregada') }}</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
