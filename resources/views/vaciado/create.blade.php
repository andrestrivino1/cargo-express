@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Programar Vaciado</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('vaciado.index') }}">Vaciado</a></li>
            <li class="breadcrumb-item active">Programar Vaciado</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('vaciado.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Contenedor: elegir de la lista o ingresar manualmente --}}
                    <div class="mb-3">
                        <label class="form-label">Contenedor <span class="text-danger">*</span></label>

                        @php $modoManual = old('numero_contenedor') && ! old('contenedor_id'); @endphp
                        <div class="btn-group btn-group-sm mb-2 d-flex" role="group">
                            <input type="radio" class="btn-check" name="modo_contenedor" id="modo_lista" value="lista" @checked(! $modoManual)>
                            <label class="btn btn-outline-secondary" for="modo_lista"><i class="bi bi-list-ul me-1"></i> Seleccionar de la lista</label>
                            <input type="radio" class="btn-check" name="modo_contenedor" id="modo_manual" value="manual" @checked($modoManual)>
                            <label class="btn btn-outline-secondary" for="modo_manual"><i class="bi bi-pencil me-1"></i> Ingresar manualmente</label>
                        </div>

                        {{-- Lista --}}
                        <div id="bloque_lista" class="{{ $modoManual ? 'd-none' : '' }}">
                            <select class="form-select @error('contenedor_id') is-invalid @enderror" id="contenedor_id" name="contenedor_id">
                                <option value="">Seleccione un contenedor</option>
                                @foreach($contenedores as $contenedor)
                                <option value="{{ $contenedor->id }}" {{ old('contenedor_id') == $contenedor->id ? 'selected' : '' }}>
                                    {{ $contenedor->numero }} - {{ $contenedor->estado->label() }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Solo se muestran contenedores con estado "En Patio".</div>
                        </div>

                        {{-- Manual --}}
                        <div id="bloque_manual" class="{{ $modoManual ? '' : 'd-none' }}">
                            <input type="text" class="form-control @error('numero_contenedor') is-invalid @enderror"
                                   id="numero_contenedor" name="numero_contenedor" maxlength="20"
                                   value="{{ old('numero_contenedor') }}" placeholder="N° de contenedor (ej. MEDU1234567)">
                            <div class="form-text">Si no está en la lista, escríbelo aquí (se registrará en patio).</div>
                        </div>

                        @error('contenedor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('numero_contenedor')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Fecha Programada --}}
                    <div class="mb-3">
                        <label for="fecha_programada" class="form-label">Fecha Programada <span class="text-danger">*</span></label>
                        <input type="date"
                               class="form-control @error('fecha_programada') is-invalid @enderror"
                               id="fecha_programada"
                               name="fecha_programada"
                               value="{{ old('fecha_programada') }}"
                               required>
                        @error('fecha_programada')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notas --}}
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control @error('notas') is-invalid @enderror"
                                  id="notas"
                                  name="notas"
                                  rows="3"
                                  placeholder="Observaciones o instrucciones adicionales...">{{ old('notas') }}</textarea>
                        @error('notas')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fotos --}}
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-camera me-1 text-primary"></i> Fotos del Contenedor
                        </label>
                        <div id="fotos-lista">
                            <div class="input-group mb-2 foto-row">
                                <input type="file" class="form-control" name="fotos[]" accept="image/jpeg,image/png,image/webp">
                                <button type="button" class="btn btn-outline-danger btn-del-foto"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-foto">
                            <i class="bi bi-plus"></i> Agregar otra foto
                        </button>
                        <div class="form-text">Puede agregar varias fotos (una por fila). JPG, PNG, WEBP. Máx 5 MB c/u. (Opcional)</div>
                        @error('fotos.*')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    {{-- Botones --}}
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calendar-check me-1"></i> Programar Vaciado
                        </button>
                        <a href="{{ route('vaciado.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-1"></i> Instrucciones</h6>
                <ul class="small mb-0">
                    <li>Seleccione el contenedor a vaciar (solo aparecen los que están "En Patio").</li>
                    <li>La fecha programada debe ser posterior a hoy.</li>
                    <li>Las notas son opcionales — úsalas para instrucciones especiales.</li>
                    <li>Adjunte fotos del estado actual del contenedor si lo desea.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle contenedor: lista vs manual
    const bloqueLista = document.getElementById('bloque_lista');
    const bloqueManual = document.getElementById('bloque_manual');
    const selectCont = document.getElementById('contenedor_id');
    const inputManual = document.getElementById('numero_contenedor');
    function aplicarModo(modo) {
        const manual = modo === 'manual';
        bloqueManual.classList.toggle('d-none', !manual);
        bloqueLista.classList.toggle('d-none', manual);
        if (manual) { selectCont.value = ''; } else { inputManual.value = ''; }
    }
    document.querySelectorAll('input[name="modo_contenedor"]').forEach(r => {
        r.addEventListener('change', () => aplicarModo(r.value));
    });

    const lista = document.getElementById('fotos-lista');
    const addBtn = document.getElementById('add-foto');

    function nuevaFila() {
        const row = document.createElement('div');
        row.className = 'input-group mb-2 foto-row';
        row.innerHTML = '<input type="file" class="form-control" name="fotos[]" accept="image/jpeg,image/png,image/webp">' +
            '<button type="button" class="btn btn-outline-danger btn-del-foto"><i class="bi bi-x"></i></button>';
        lista.appendChild(row);
    }

    if (addBtn) addBtn.addEventListener('click', nuevaFila);
    if (lista) lista.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-foto') && lista.querySelectorAll('.foto-row').length > 1) {
            e.target.closest('.foto-row').remove();
        }
    });
});
</script>
@endpush