@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-bar-chart fs-4 me-2 text-primary"></i>
        <h2 class="mb-0">Reportes</h2>
    </div>

    <div class="row">
        {{-- Reporte de Operación --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-data display-4 text-primary"></i>
                    </div>
                    <h5 class="card-title">Reporte de Operación</h5>
                    <p class="card-text text-muted">
                        Consulte movimientos (gate events), novedades y resumen de días de almacenamiento por cliente.
                        Exporte en Excel o PDF.
                    </p>
                    <a href="{{ route('reportes.operacion') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-right me-1"></i> Ver Reporte
                    </a>
                </div>
            </div>
        </div>

        @php
            $reportes = [
                ['route' => 'reportes.inventario-por-cliente', 'icon' => 'bi-graph-up', 'title' => 'Inventario por cliente', 'desc' => 'Saldo de inventario disponible agrupado por cliente.'],
                ['route' => 'reportes.ingresos', 'icon' => 'bi-box-arrow-in-right', 'title' => 'Ingresos', 'desc' => 'Movimientos de entrada de mercancía al inventario.'],
                ['route' => 'reportes.salidas', 'icon' => 'bi-box-arrow-right', 'title' => 'Salidas', 'desc' => 'Movimientos de salida (despachos) del inventario.'],
                ['route' => 'reportes.movimientos', 'icon' => 'bi-arrow-left-right', 'title' => 'Historial de movimientos', 'desc' => 'Todas las entradas y salidas con su saldo resultante.'],
                ['route' => 'reportes.novedades', 'icon' => 'bi-exclamation-triangle', 'title' => 'Novedades', 'desc' => 'Averías, faltantes y daños registrados en recepción.'],
                ['route' => 'reportes.evidencias', 'icon' => 'bi-images', 'title' => 'Evidencias y trazabilidad', 'desc' => 'Fotografías de evidencia registradas en el sistema.'],
            ];
        @endphp

        @foreach ($reportes as $reporte)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3"><i class="bi {{ $reporte['icon'] }} display-4 text-primary"></i></div>
                    <h5 class="card-title">{{ $reporte['title'] }}</h5>
                    <p class="card-text text-muted">{{ $reporte['desc'] }}</p>
                    <a href="{{ route($reporte['route']) }}" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i> Ver Reporte</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
