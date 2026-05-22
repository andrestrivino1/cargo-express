@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-left-right me-2"></i>Detalle de Transferencia</h2>
    <a href="{{ route('transferencias.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información General</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>ID Transferencia:</strong></div>
                    <div class="col-md-8">#{{ $transferencia->id }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Fecha:</strong></div>
                    <div class="col-md-8">{{ $transferencia->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Tipo:</strong></div>
                    <div class="col-md-8">
                        @if($transferencia->tipo === 'entre_modulos')
                            <span class="badge bg-primary">Entre Módulos</span>
                        @else
                            <span class="badge bg-warning text-dark">Entre Clientes</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Realizada por:</strong></div>
                    <div class="col-md-8">{{ $transferencia->usuario->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Producto Transferido</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Producto:</strong></div>
                    <div class="col-md-8">{{ $transferencia->referenciaOrigen->producto->nombre ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Código Referencia:</strong></div>
                    <div class="col-md-8">{{ $transferencia->referenciaOrigen->codigo ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Cantidad Transferida:</strong></div>
                    <div class="col-md-8"><span class="fw-bold text-primary">{{ $transferencia->cantidad }}</span></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="bi bi-box-arrow-up me-1"></i> Origen</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Módulo/Posición:</strong><br>
                            {{ $transferencia->ubicacionOrigen->modulo ?? '-' }} / {{ $transferencia->ubicacionOrigen->posicion ?? '-' }}
                        </p>
                        @if($transferencia->tipo === 'entre_clientes')
                            <p><strong>Cliente:</strong><br>{{ $transferencia->clienteOrigen->name ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-box-arrow-in-down me-1"></i> Destino</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Módulo/Posición:</strong><br>
                            {{ $transferencia->ubicacionDestino->modulo ?? '-' }} / {{ $transferencia->ubicacionDestino->posicion ?? '-' }}
                        </p>
                        @if($transferencia->tipo === 'entre_clientes')
                            <p><strong>Cliente:</strong><br>{{ $transferencia->clienteDestino->name ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($transferencia->tipo === 'entre_clientes')
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-shield-check me-1"></i> Autorización</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Motivo:</strong></div>
                    <div class="col-md-8">{{ $transferencia->motivo ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Autorización:</strong></div>
                    <div class="col-md-8">
                        <span class="text-success fw-bold">{{ $transferencia->autorizacion_cliente }}</span>
                    </div>
                </div>
                <a href="{{ route('transferencias.constancia', $transferencia) }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Constancia PDF
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
