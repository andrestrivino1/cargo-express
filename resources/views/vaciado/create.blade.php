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

                    {{-- Contenedor --}}
                    <div class="mb-3">
                        <label for="contenedor_id" class="form-label">Contenedor <span class="text-danger">*</span></label>
                        <select class="form-select @error('contenedor_id') is-invalid @enderror"
                                id="contenedor_id"
                                name="contenedor_id"
                                required>
                            <option value="">Seleccione un contenedor</option>
                            @foreach($contenedores as $contenedor)
                            <option value="{{ $contenedor->id }}" {{ old('contenedor_id') == $contenedor->id ? 'selected' : '' }}>
                                {{ $contenedor->numero }} - {{ $contenedor->estado->label() }}
                            </option>
                            @endforeach
                        </select>
                        @error('contenedor_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Solo se muestran contenedores con estado "En Patio".</div>
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
                        <label for="fotos" class="form-label">
                            <i class="bi bi-camera me-1 text-primary"></i> Fotos del Contenedor
                        </label>
                        <input type="file"
                               class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                               id="fotos"
                               name="fotos[]"
                               multiple
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Formatos: JPG, PNG, WEBP. Máximo 5 MB por foto. (Opcional)</div>
                        @error('fotos')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="preview-fotos" class="d-flex flex-wrap gap-2 mt-2"></div>
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
    const fotosInput = document.getElementById('fotos');
    const previewFotos = document.getElementById('preview-fotos');

    if (fotosInput) {
        fotosInput.addEventListener('change', function () {
            previewFotos.innerHTML = '';
            Array.from(this.files).forEach(function (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;';
                    img.title = file.name;
                    previewFotos.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
</script>
@endpush