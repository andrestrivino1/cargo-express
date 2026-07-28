@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-flag me-2"></i>Vehículo sin cita</h2>
    <a href="{{ route('porteria.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Esto deja constancia para revisión posterior. <strong>No crea una cita</strong>:
    agendar corresponde al área de citas.
</div>

<form method="POST" action="{{ route('porteria.novedad.store') }}">
    @csrf

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Placa del vehículo</label>
                    <input type="text" name="placa" value="{{ old('placa', $placa) }}" maxlength="20"
                           class="form-control form-control-lg @error('placa') is-invalid @enderror">
                    @error('placa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Número de contenedor</label>
                    <input type="text" name="numero_contenedor" value="{{ old('numero_contenedor') }}" maxlength="20"
                           class="form-control form-control-lg @error('numero_contenedor') is-invalid @enderror">
                    @error('numero_contenedor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Indica al menos la placa o el contenedor.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">¿Qué pasó? <span class="text-danger">*</span></label>
                    <textarea name="descripcion" rows="3" maxlength="500" required
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Ej.: se presentó sin cita, dice traer carga del cliente X.">{{ old('descripcion') }}</textarea>
                    @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning btn-lg w-100">
                <i class="bi bi-flag me-1"></i> Registrar novedad
            </button>
        </div>
    </div>
</form>
@endsection
