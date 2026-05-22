@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Bienvenido, {{ auth()->user()->name }}</h2>

    <div class="row g-4">
        {{-- Cards para Cliente --}}
        @role('cliente')
        <div class="col-md-4 col-lg-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <i class="bi bi-archive text-info" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Mi Inventario</h5>
                    <p class="display-6 fw-bold text-info">{{ $clienteReferenciasCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-info text-center">
                    <a href="{{ route('inventario.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver inventario
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <i class="bi bi-truck text-primary" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Mis Entregas</h5>
                    <p class="display-6 fw-bold text-primary">{{ $clienteEntregasCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-primary text-center">
                    <a href="{{ route('entregas.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver entregas
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <i class="bi bi-plus-circle text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Solicitar Orden de Cargue</h5>
                    <p class="text-muted mt-2">Crear nueva orden de despacho</p>
                </div>
                <div class="card-footer bg-transparent border-success text-center">
                    <a href="{{ route('entregas.create') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Crear orden
                    </a>
                </div>
            </div>
        </div>
        @endrole

        {{-- Cards para Portero --}}
        @role('portero|supervisor|gerente|administrador')
        <div class="col-md-4 col-lg-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <i class="bi bi-box-arrow-in-right text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Pendientes Ingreso</h5>
                    <p class="display-6 fw-bold text-success">{{ $porteroIngresosCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-success text-center">
                    <a href="{{ route('gate-in.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver Ingreso
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <i class="bi bi-box-arrow-right text-danger" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Pendientes Salida</h5>
                    <p class="display-6 fw-bold text-danger">{{ $porteroSalidasCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-danger text-center">
                    <a href="{{ route('gate-out.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver Salida
                    </a>
                </div>
            </div>
        </div>
        @endrole

        {{-- Cards para Coordinador --}}
        @role('coordinador|supervisor|gerente|administrador')
        <div class="col-md-4 col-lg-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-check text-warning" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Solicitudes Pendientes</h5>
                    <p class="display-6 fw-bold text-warning">{{ $coordinadorSolicitudesCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-warning text-center">
                    <a href="{{ route('solicitudes.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver pendientes
                    </a>
                </div>
            </div>
        </div>
        @endrole

        {{-- Cards para Operador --}}
        @role('operador|supervisor|gerente|administrador')
        <div class="col-md-4 col-lg-3">
            <div class="card border-secondary h-100">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam text-secondary" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Vaciados en Proceso</h5>
                    <p class="display-6 fw-bold text-secondary">{{ $operadorVaciadosCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-secondary text-center">
                    <a href="{{ route('vaciado.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ver vaciados
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-dark h-100">
                <div class="card-body text-center">
                    <i class="bi bi-geo-alt text-dark" style="font-size: 2.5rem;"></i>
                    <h5 class="card-title mt-3">Referencias por Ubicar</h5>
                    <p class="display-6 fw-bold text-dark">{{ $operadorSinUbicarCount ?? 0 }}</p>
                </div>
                <div class="card-footer bg-transparent border-dark text-center">
                    <a href="{{ route('inventario.ubicar') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-right-circle me-1"></i> Ubicar referencias
                    </a>
                </div>
            </div>
        </div>
        @endrole
    </div>
</div>
@endsection