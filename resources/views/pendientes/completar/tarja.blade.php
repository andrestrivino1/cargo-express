@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px">
    <h1 class="h3 mb-3">Completar Tarja #{{ $modelo->id }}</h1>
    @include('pendientes.completar._campos_comunes')

    <form method="POST" action="{{ route('pendientes.actualizar', ['type' => $tipoSlug, 'id' => $modelo->id]) }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label">Despachador *</label>
                <select name="despachador_id" class="form-select @error('despachador_id') is-invalid @enderror" required>
                    <option value="">— Selecciona —</option>
                    @foreach (\App\Models\User::role('despachador')->orderBy('name')->get() as $d)
                        <option value="{{ $d->id }}" @selected(old('despachador_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('despachador_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Vehículo</label>
                <input type="text" name="vehiculo" class="form-control" value="{{ old('vehiculo', $modelo->vehiculo) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Conductor</label>
                <input type="text" name="conductor" class="form-control" value="{{ old('conductor', $modelo->conductor) }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control">{{ old('observaciones', $modelo->observaciones) }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('pendientes.index') }}" class="btn btn-link">Cancelar</a>
        </div>
    </form>
</div>
@endsection
