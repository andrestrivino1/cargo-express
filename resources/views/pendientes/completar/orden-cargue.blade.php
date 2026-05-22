@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px">
    <h1 class="h3 mb-3">Completar Orden de cargue #{{ $modelo->id }}</h1>
    @include('pendientes.completar._campos_comunes')

    <form method="POST" action="{{ route('pendientes.actualizar', ['type' => $tipoSlug, 'id' => $modelo->id]) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Despachador *</label>
            <select name="despachador_id" class="form-select @error('despachador_id') is-invalid @enderror" required>
                <option value="">— Selecciona —</option>
                @foreach (\App\Models\User::role('despachador')->orderBy('name')->get() as $d)
                    <option value="{{ $d->id }}" @selected(old('despachador_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
            @error('despachador_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea name="notas" class="form-control">{{ old('notas', $modelo->notas) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('pendientes.index') }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
