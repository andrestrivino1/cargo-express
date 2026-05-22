@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px">
    <h1 class="h3 mb-3">Completar Orden de servicio #{{ $modelo->id }}</h1>
    @include('pendientes.completar._campos_comunes')

    <form method="POST" action="{{ route('pendientes.actualizar', ['type' => $tipoSlug, 'id' => $modelo->id]) }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehículo (placa) *</label>
                <input type="text" name="vehiculo" class="form-control @error('vehiculo') is-invalid @enderror" required value="{{ old('vehiculo') }}">
                @error('vehiculo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Conductor *</label>
                <input type="text" name="conductor" class="form-control @error('conductor') is-invalid @enderror" required value="{{ old('conductor') }}">
                @error('conductor') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Documento conductor</label>
                <input type="text" name="conductor_documento" class="form-control" value="{{ old('conductor_documento') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cita en puerto *</label>
                <input type="datetime-local" name="cita_puerto" class="form-control @error('cita_puerto') is-invalid @enderror" required value="{{ old('cita_puerto', $modelo->cita_puerto?->format('Y-m-d\TH:i')) }}">
                @error('cita_puerto') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('pendientes.index') }}" class="btn btn-link">Cancelar</a>
        </div>
    </form>
</div>
@endsection
