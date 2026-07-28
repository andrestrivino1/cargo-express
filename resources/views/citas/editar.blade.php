@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil-square me-2"></i>Editar cita — {{ $cita->numero_contenedor }}</h2>
    <a href="{{ route('citas.show', $cita) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

@if ($cita->estadoEfectivo() === \App\Enums\CitaEstado::Vencida)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-circle me-1"></i>
    Esta cita está vencida. Cambiar la fecha posible de llegada la reprograma.
</div>
@endif

<form method="POST" action="{{ route('citas.update', $cita) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-body">
            @include('citas._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="submit" form="form-cancelar-cita" class="btn btn-outline-danger"
                    onclick="return confirm('¿Cancelar esta cita? El vehículo no podrá validarse en portería.')">
                <i class="bi bi-x-circle me-1"></i> Cancelar cita
            </button>
            <div>
                <a href="{{ route('citas.show', $cita) }}" class="btn btn-outline-secondary">Descartar cambios</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</form>

<form id="form-cancelar-cita" method="POST" action="{{ route('citas.cancelar', $cita) }}" class="d-none">
    @csrf
</form>
@endsection
