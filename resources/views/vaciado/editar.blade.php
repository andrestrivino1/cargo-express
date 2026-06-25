@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Orden de Vaciado #{{ $ordenVaciado->id }}</h1>
    <a href="{{ route('vaciado.show', $ordenVaciado) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('vaciado.update', $ordenVaciado) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="fecha_programada" class="form-label">Fecha programada <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('fecha_programada') is-invalid @enderror"
                       id="fecha_programada" name="fecha_programada"
                       value="{{ old('fecha_programada', $ordenVaciado->fecha_programada?->format('Y-m-d')) }}" required>
                @error('fecha_programada') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="supervisor_id" class="form-label">Supervisor <span class="text-danger">*</span></label>
                <select class="form-select @error('supervisor_id') is-invalid @enderror" id="supervisor_id" name="supervisor_id" required>
                    <option value="">-- Seleccionar supervisor --</option>
                    @foreach($supervisores as $supervisor)
                        <option value="{{ $supervisor->id }}" {{ (string) old('supervisor_id', $ordenVaciado->supervisor_id) === (string) $supervisor->id ? 'selected' : '' }}>
                            {{ $supervisor->name }}
                        </option>
                    @endforeach
                </select>
                @error('supervisor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="notas" class="form-label">Notas</label>
                <textarea class="form-control @error('notas') is-invalid @enderror"
                          id="notas" name="notas" rows="3">{{ old('notas', $ordenVaciado->notas) }}</textarea>
                @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Fotos del vaciado --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-images me-1"></i> Fotos del vaciado</label>
                @if($ordenVaciado->fotos->count() > 0)
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($ordenVaciado->fotos as $foto)
                    <a href="{{ $foto->url }}" target="_blank">
                        <img src="{{ $foto->url }}" alt="{{ $foto->nombre }}" class="rounded border" style="width: 90px; height: 90px; object-fit: cover;">
                    </a>
                    @endforeach
                </div>
                @endif
                <div id="fotos-lista">
                    <div class="input-group mb-2 foto-row">
                        <input type="file" class="form-control @error('fotos.*') is-invalid @enderror" name="fotos[]" accept="image/jpeg,image/png,image/webp">
                        <button type="button" class="btn btn-outline-danger btn-del-foto"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-foto"><i class="bi bi-plus"></i> Agregar otra foto</button>
                <div class="form-text">Una foto por fila. JPG, PNG, WEBP. Máx 5 MB c/u. Se suman a las existentes al guardar.</div>
                @error('fotos.*')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                <a href="{{ route('vaciado.show', $ordenVaciado) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@include('components.historial-auditoria', ['registro' => $ordenVaciado])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lista = document.getElementById('fotos-lista');
    const addBtn = document.getElementById('add-foto');
    if (addBtn) addBtn.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 foto-row';
        row.innerHTML = '<input type="file" class="form-control" name="fotos[]" accept="image/jpeg,image/png,image/webp">' +
            '<button type="button" class="btn btn-outline-danger btn-del-foto"><i class="bi bi-x"></i></button>';
        lista.appendChild(row);
    });
    if (lista) lista.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-foto') && lista.querySelectorAll('.foto-row').length > 1) {
            e.target.closest('.foto-row').remove();
        }
    });
});
</script>
@endpush
