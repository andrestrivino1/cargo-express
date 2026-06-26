@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-pencil-square me-2"></i>Editar Salida ODC-{{ $tarja->consecutivo_odc }}</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('salida.index') }}">Salida</a></li>
            <li class="breadcrumb-item"><a href="{{ route('salida.show', $tarja) }}">ODC-{{ $tarja->consecutivo_odc }}</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i> Aquí editas los datos de la salida (cliente/NIT, conductor, vehículo, transporte, fecha y observaciones).
    La <strong>mercancía despachada no se modifica</strong> para no alterar el inventario ya descontado.
</div>

<form action="{{ route('salida.update', $tarja) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header">Datos de la salida</div>
        <div class="card-body row g-3">
            <div class="col-md-5">
                <label class="form-label">Cliente</label>
                <input type="text" class="form-control" value="{{ $tarja->ordenCargue?->cliente?->name }}" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">NIT del cliente</label>
                <input type="text" name="nit" value="{{ old('nit', $tarja->ordenCargue?->cliente?->nit) }}" class="form-control" maxlength="30">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha de salida <span class="text-danger">*</span></label>
                <input type="date" name="fecha_salida" value="{{ old('fecha_salida', $tarja->fecha_entrega?->format('Y-m-d')) }}" class="form-control" required>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Datos del conductor y vehículo</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Nombre del conductor <span class="text-danger">*</span></label>
                <input type="text" name="conductor" value="{{ old('conductor', $tarja->conductor) }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cédula</label>
                <input type="text" name="conductor_cedula" value="{{ old('conductor_cedula', $tarja->conductor_cedula) }}" class="form-control" maxlength="20">
            </div>
            <div class="col-md-2">
                <label class="form-label">Placa <span class="text-danger">*</span></label>
                <input type="text" name="placa_vehiculo" value="{{ old('placa_vehiculo', $tarja->vehiculo) }}" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Transportador <span class="text-danger">*</span></label>
                <input type="text" name="transportador" value="{{ old('transportador', $tarja->transportador) }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Destino <span class="text-danger">*</span></label>
                <input type="text" name="destino" value="{{ old('destino', $tarja->destino) }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-12">
                <label class="form-label">Observaciones / novedades</label>
                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones', $tarja->observaciones) }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
    <a href="{{ route('salida.show', $tarja) }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
