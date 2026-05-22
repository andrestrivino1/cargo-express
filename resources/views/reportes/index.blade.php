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

        {{-- Placeholder para futuros reportes --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100 border-dashed">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-graph-up display-4 text-muted"></i>
                    </div>
                    <h5 class="card-title text-muted">Reporte de Inventario</h5>
                    <p class="card-text text-muted">
                        Próximamente: análisis detallado de inventario y ocupación de patio.
                    </p>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-clock me-1"></i> Próximamente
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100 border-dashed">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-pie-chart display-4 text-muted"></i>
                    </div>
                    <h5 class="card-title text-muted">Reporte Estadístico</h5>
                    <p class="card-text text-muted">
                        Próximamente: estadísticas generales de operación y rendimiento.
                    </p>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-clock me-1"></i> Próximamente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
