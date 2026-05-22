@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="h3">Crear Solicitud</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('solicitudes.store') }}" method="POST" enctype="multipart/form-data" id="form-solicitud">
            @csrf

            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                <select class="form-select @error('cliente_id') is-invalid @enderror"
                        id="cliente_id" name="cliente_id" required>
                    <option value="">-- Seleccionar cliente --</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->name }} ({{ $cliente->email }})
                        </option>
                    @endforeach
                </select>
                @error('cliente_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="numero_contenedor" class="form-label">Numero de Contenedor <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('numero_contenedor') is-invalid @enderror"
                       id="numero_contenedor" name="numero_contenedor" value="{{ old('numero_contenedor') }}"
                       maxlength="20" required>
                @error('numero_contenedor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="fecha_solicitud" class="form-label">Fecha de la Solicitud <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('fecha_solicitud') is-invalid @enderror"
                       id="fecha_solicitud" name="fecha_solicitud"
                       value="{{ old('fecha_solicitud', now()->format('Y-m-d')) }}" required>
                <small class="text-muted">Puedes seleccionar una fecha anterior o posterior a hoy.</small>
                @error('fecha_solicitud')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="naviera" class="form-label">Naviera</label>
                <input type="text" class="form-control @error('naviera') is-invalid @enderror"
                       id="naviera" name="naviera" value="{{ old('naviera') }}" maxlength="100">
                @error('naviera')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="puerto_origen" class="form-label">Puerto de Origen</label>
                <input type="text" class="form-control @error('puerto_origen') is-invalid @enderror"
                       id="puerto_origen" name="puerto_origen" value="{{ old('puerto_origen') }}" maxlength="100">
                @error('puerto_origen')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripcion</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                          id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Zona de carga de archivos con preview --}}
            <div class="mb-3">
                <label class="form-label">Documentos e Imágenes <span class="text-danger">*</span></label>

                <div id="drop-zone"
                     class="border border-2 rounded-3 p-4 text-center @error('documentos') border-danger @enderror"
                     style="border-style: dashed !important; cursor: pointer; transition: all .2s; min-height: 130px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <i class="bi bi-cloud-upload fs-2 text-muted mb-2"></i>
                    <p class="mb-1 text-muted">Arrastra archivos aquí o <span class="text-primary fw-semibold">haz clic para seleccionar</span></p>
                    <small class="text-muted">PDF, JPG, PNG — máximo 10 MB por archivo</small>
                    <input type="file" id="documentos" name="documentos[]"
                           multiple accept=".pdf,.jpg,.jpeg,.png" class="d-none">
                </div>

                <div id="doc-error" class="text-danger small mt-1 d-none"><i class="bi bi-exclamation-circle me-1"></i>Debes adjuntar al menos un archivo.</div>
                @error('documentos')
                    <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
                @error('documentos.*')
                    <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror

                {{-- Preview grid --}}
                <div id="preview-grid" class="row g-2 mt-2"></div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Crear Solicitud
                </button>
                <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
#drop-zone.dragover {
    border-color: #0d6efd !important;
    background: #f0f7ff;
}
.preview-item {
    position: relative;
}
.preview-item .remove-btn {
    position: absolute;
    top: 4px;
    right: 20px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(220,53,69,.85);
    color: #fff;
    border: none;
    font-size: 11px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}
.preview-item img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}
.preview-item .pdf-thumb {
    width: 100%;
    height: 90px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #6c757d;
    overflow: hidden;
    padding: 4px;
    text-align: center;
}
.preview-item .file-name {
    font-size: 0.7rem;
    color: #6c757d;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    max-width: 100%;
    margin-top: 3px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone    = document.getElementById('drop-zone');
    const fileInput   = document.getElementById('documentos');
    const previewGrid = document.getElementById('preview-grid');
    let fileList = [];

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        addFiles(Array.from(e.dataTransfer.files));
    });

    fileInput.addEventListener('change', () => {
        const captured = Array.from(fileInput.files); // capturar antes de limpiar
        fileInput.value = '';                          // limpiar para poder re-seleccionar el mismo archivo
        addFiles(captured);
    });

    function addFiles(newFiles) {
        newFiles.forEach(file => {
            if (fileList.some(f => f.name === file.name && f.size === file.size)) return;
            fileList.push(file);
            renderPreview(file);
        });
        syncInput();
    }

    function renderPreview(file) {
        const col = document.createElement('div');
        col.className = 'col-6 col-sm-4 col-md-3 col-lg-2 preview-item';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-btn';
        removeBtn.innerHTML = '&times;';
        removeBtn.title = 'Quitar';
        removeBtn.addEventListener('click', () => {
            fileList = fileList.filter(f => !(f.name === file.name && f.size === file.size));
            col.remove();
            syncInput();
        });

        const nameEl = document.createElement('div');
        nameEl.className = 'file-name';
        nameEl.textContent = file.name;
        nameEl.title = file.name;

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.alt = file.name;
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; };
            reader.readAsDataURL(file);
            col.appendChild(img);
        } else {
            const thumb = document.createElement('div');
            thumb.className = 'pdf-thumb';
            thumb.innerHTML = '<i class="bi bi-file-earmark-pdf text-danger" style="font-size:2rem;"></i><span class="mt-1 px-1">' + file.name + '</span>';
            col.appendChild(thumb);
        }

        col.appendChild(removeBtn);
        col.appendChild(nameEl);
        previewGrid.appendChild(col);
    }

    function syncInput() {
        const dt = new DataTransfer();
        fileList.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    // Validar al enviar
    document.getElementById('form-solicitud').addEventListener('submit', function (e) {
        const docError = document.getElementById('doc-error');
        if (fileList.length === 0) {
            e.preventDefault();
            docError.classList.remove('d-none');
            dropZone.style.borderColor = '#dc3545';
            dropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            docError.classList.add('d-none');
            dropZone.style.borderColor = '';
        }
    });
});
</script>
@endpush