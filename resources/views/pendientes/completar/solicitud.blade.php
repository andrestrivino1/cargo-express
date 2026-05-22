@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px">
    <h1 class="h3 mb-3">Completar Solicitud #{{ $modelo->id }} ({{ $modelo->numero_contenedor }})</h1>
    @include('pendientes.completar._campos_comunes')

    <form method="POST" action="{{ route('pendientes.actualizar', ['type' => $tipoSlug, 'id' => $modelo->id]) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Naviera</label>
            <input type="text" name="naviera" class="form-control" value="{{ old('naviera', $modelo->naviera) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Puerto de origen</label>
            <input type="text" name="puerto_origen" class="form-control" value="{{ old('puerto_origen', $modelo->puerto_origen) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control">{{ old('descripcion', $modelo->descripcion) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('pendientes.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
