@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-plus me-2"></i>Nueva cita de llegada</h2>
    <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<form method="POST" action="{{ route('citas.store') }}">
    @csrf

    <div class="card">
        <div class="card-body">
            @include('citas._form')
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('citas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Agendar cita
            </button>
        </div>
    </div>
</form>
@endsection
