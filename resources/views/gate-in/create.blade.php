@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Registrar Ingreso</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('gate-in.index') }}">Ingreso</a></li>
            <li class="breadcrumb-item active">Registrar Ingreso</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('gate-in.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Solicitud pendiente de ingreso --}}
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Solicitud Pendiente de Ingreso</h5>
                        <div class="mb-3">
                            <label for="orden_servicio_id" class="form-label">Seleccionar Solicitud <span class="text-danger">*</span></label>
                            <select class="form-select @error('orden_servicio_id') is-invalid @enderror"
                                    id="orden_servicio_id"
                                    name="orden_servicio_id"
                                    required>
                                <option value="">— Seleccione una solicitud pendiente —</option>
                                @foreach($pendientesIngreso as $contenedor)
                                    @php
                                        $orden     = $contenedor->ordenServicio;
                                        $solicitud = $orden?->solicitud;
                                        $cliente   = $solicitud?->cliente;
                                    @endphp
                                    <option value="{{ $orden?->id }}"
                                        data-cliente="{{ $cliente?->name ?? 'N/A' }}"
                                        data-contenedor="{{ $solicitud?->numero_contenedor ?? $contenedor->numero }}"
                                        data-solicitud="{{ $solicitud?->id ?? '' }}"
                                        {{ old('orden_servicio_id', request('orden_servicio_id')) == $orden?->id ? 'selected' : '' }}>
                                        Sol. #{{ $solicitud?->id ?? '?' }}
                                        — {{ $cliente?->name ?? 'Sin cliente' }}
                                        — Cont: {{ $solicitud?->numero_contenedor ?? $contenedor->numero }}
                                    </option>
                                @endforeach
                            </select>
                            @error('orden_servicio_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($pendientesIngreso->isEmpty())
                                <div class="form-text text-warning mt-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    No hay solicitudes pendientes de ingreso. Primero debe asignarse una orden de servicio a una solicitud.
                                </div>
                            @endif
                        </div>

                        {{-- Panel informativo de la solicitud seleccionada --}}
                        <div id="orden-info" class="d-none">
                            <div class="py-3 px-4" style="background: #f0f7ff; border-left: 4px solid #0d6efd; border-radius: 8px;">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-circle fs-4 text-primary"></i>
                                            <div>
                                                <div class="text-muted mb-0" style="font-size:0.72rem;">Cliente</div>
                                                <strong id="info-cliente" class="text-dark">—</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-box-seam fs-4 text-primary"></i>
                                            <div>
                                                <div class="text-muted mb-0" style="font-size:0.72rem;">No. Contenedor</div>
                                                <strong id="info-contenedor" class="text-dark">—</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-hash fs-4 text-primary"></i>
                                            <div>
                                                <div class="text-muted mb-0" style="font-size:0.72rem;">Consecutivo Solicitud</div>
                                                <strong id="info-solicitud" class="text-dark">—</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- Datos del Vehiculo y Contenedor --}}
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Datos del Vehiculo y Contenedor</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="placa" class="form-label">Placa del Vehiculo <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('placa') is-invalid @enderror"
                                       id="placa"
                                       name="placa"
                                       value="{{ old('placa') }}"
                                       placeholder="Ej: ABC-123"
                                       maxlength="20"
                                       required>
                                @error('placa')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="numero_contenedor" class="form-label">No. Contenedor <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('numero_contenedor') is-invalid @enderror"
                                       id="numero_contenedor"
                                       name="numero_contenedor"
                                       value="{{ old('numero_contenedor') }}"
                                       placeholder="Ej: MSKU1234567"
                                       maxlength="20"
                                       required>
                                @error('numero_contenedor')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Observaciones</h5>
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control @error('notas') is-invalid @enderror"
                                      id="notas"
                                      name="notas"
                                      rows="2"
                                      placeholder="Observaciones adicionales...">{{ old('notas') }}</textarea>
                            @error('notas')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Productos del Contenedor --}}
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Productos en el Contenedor</h5>
                        <p class="text-muted small">Seleccione los productos que vienen dentro del contenedor y la cantidad de cada uno.</p>

                        <div id="productos-container">
                            <div class="producto-row border rounded p-3 mb-2">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Producto <span class="text-danger">*</span></label>
                                        <select name="productos[0][producto_id]" class="form-select producto-select" required>
                                            <option value="">Seleccione un producto</option>
                                            @foreach($productos as $producto)
                                                <option value="{{ $producto->id }}"
                                                    data-nombre="{{ $producto->nombre }}"
                                                    data-medidas="{{ $producto->medidas }}"
                                                    data-calibre="{{ $producto->calibre }}"
                                                    data-empaque="{{ $producto->empaque }}">
                                                    {{ $producto->nombre }} {{ $producto->medidas ? '('.$producto->medidas.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label">Codigo</label>
                                        <input type="text" name="productos[0][codigo]" class="form-control" placeholder="REF-001">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                                        <input type="number" name="productos[0][cantidad]" class="form-control" min="1" required>
                                    </div>
                                    <div class="col-md-3 mb-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-producto" style="display:none;">
                                            <i class="bi bi-trash"></i> Quitar
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <input type="text" name="productos[0][descripcion]" class="form-control form-control-sm descripcion-field" placeholder="Descripcion adicional (opcional)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="add-producto" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="bi bi-plus-circle me-1"></i> Agregar otro producto
                        </button>

                        @error('productos')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Evidencia del Contenedor --}}
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2"><i class="bi bi-camera me-1"></i> Evidencia del Contenedor</h5>

                        {{-- Fotos --}}
                        <div class="mb-3">
                            <label for="fotos" class="form-label fw-semibold">
                                <i class="bi bi-image me-1 text-primary"></i> Fotos del Contenedor
                            </label>
                            <input type="file"
                                   class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                                   id="fotos"
                                   name="fotos[]"
                                   multiple
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Formatos: JPG, PNG, WEBP. Máximo 5 MB por foto.</div>
                            @error('fotos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('fotos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="preview-fotos" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        {{-- Documentos --}}
                        <div class="mb-3">
                            <label for="documentos" class="form-label fw-semibold">
                                <i class="bi bi-file-earmark-text me-1 text-success"></i> Documentos Adjuntos
                            </label>
                            <input type="file"
                                   class="form-control @error('documentos') is-invalid @enderror @error('documentos.*') is-invalid @enderror"
                                   id="documentos"
                                   name="documentos[]"
                                   multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <div class="form-text">Formatos: PDF, Word, Excel. Máximo 10 MB por documento.</div>
                            @error('documentos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('documentos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="lista-documentos" class="mt-2"></div>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Registrar Ingreso
                        </button>
                        <a href="{{ route('gate-in.index') }}" class="btn btn-outline-secondary">
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
                    <li>Seleccione la solicitud pendiente de ingreso.</li>
                    <li>Registre la placa del vehiculo y el numero del contenedor.</li>
                    <li><strong>Seleccione los productos</strong> que vienen en el contenedor y sus cantidades.</li>
                    <li>Adjunte fotos o documentos como evidencia del ingreso.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let productoIndex = 1;
    const container = document.getElementById('productos-container');
    const addBtn = document.getElementById('add-producto');

    // ── Orden de Servicio: panel informativo al cambiar selección ──────────
    const ordenSelect = document.getElementById('orden_servicio_id');
    const infoPanel   = document.getElementById('orden-info');

    function actualizarPanelOrden() {
        const option = ordenSelect.options[ordenSelect.selectedIndex];
        if (!option || !option.value) {
            infoPanel.classList.add('d-none');
            return;
        }

        document.getElementById('info-cliente').textContent    = option.dataset.cliente    || '—';
        document.getElementById('info-contenedor').textContent = option.dataset.contenedor  || '—';
        document.getElementById('info-solicitud').textContent  = option.dataset.solicitud
            ? '#' + option.dataset.solicitud : '—';

        infoPanel.classList.remove('d-none');

        // Auto-completar número de contenedor si el campo está vacío
        const numContenedor = document.getElementById('numero_contenedor');
        if (option.dataset.contenedor && !numContenedor.value) {
            numContenedor.value = option.dataset.contenedor;
        }
    }

    ordenSelect.addEventListener('change', actualizarPanelOrden);

    // Ejecutar al cargar si ya hay un valor pre-seleccionado (ej: desde pendientes)
    if (ordenSelect.value) actualizarPanelOrden();

    // ── Productos: agregar/quitar filas ────────────────────────────────────
    addBtn.addEventListener('click', function() {
        const row = container.querySelector('.producto-row').cloneNode(true);

        // Update names with new index
        row.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/productos\[\d+\]/, 'productos[' + productoIndex + ']');
        });

        // Clear values
        row.querySelectorAll('input').forEach(function(el) {
            if (el.type !== 'hidden') {
                el.value = el.name.includes('unidad_medida') ? 'unidades' : '';
            }
        });
        row.querySelector('select').selectedIndex = 0;

        // Show remove button
        const removeBtn = row.querySelector('.remove-producto');
        removeBtn.style.display = 'block';
        removeBtn.addEventListener('click', function() {
            row.remove();
            updateRemoveButtons();
        });

        container.appendChild(row);
        productoIndex++;
        updateRemoveButtons();
    });

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.producto-row');
        rows.forEach(function(row, idx) {
            const btn = row.querySelector('.remove-producto');
            btn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }

    // Auto-fill description when product is selected
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('producto-select')) {
            const option = e.target.options[e.target.selectedIndex];
            const row = e.target.closest('.producto-row');
            const desc = row.querySelector('.descripcion-field');
            if (option.value) {
                const parts = [option.dataset.nombre];
                if (option.dataset.medidas) parts.push(option.dataset.medidas);
                if (option.dataset.calibre) parts.push(option.dataset.calibre);
                if (option.dataset.empaque) parts.push('Empaque: ' + option.dataset.empaque);
                desc.value = parts.join(' - ');
            }
        }
    });

    // ── Vista previa de fotos ──────────────────────────────────────────────
    const fotosInput = document.getElementById('fotos');
    const previewFotos = document.getElementById('preview-fotos');

    fotosInput.addEventListener('change', function() {
        previewFotos.innerHTML = '';
        Array.from(this.files).forEach(function(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;';
                img.title = file.name;
                previewFotos.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });

    // ── Lista de documentos seleccionados ─────────────────────────────────
    const documentosInput = document.getElementById('documentos');
    const listaDocumentos = document.getElementById('lista-documentos');

    const iconos = {
        'pdf': 'bi-file-earmark-pdf text-danger',
        'doc': 'bi-file-earmark-word text-primary',
        'docx': 'bi-file-earmark-word text-primary',
        'xls': 'bi-file-earmark-excel text-success',
        'xlsx': 'bi-file-earmark-excel text-success',
    };

    documentosInput.addEventListener('change', function() {
        listaDocumentos.innerHTML = '';
        if (!this.files.length) return;

        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush';

        Array.from(this.files).forEach(function(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const icono = iconos[ext] || 'bi-file-earmark text-secondary';
            const size = (file.size / 1024).toFixed(1) + ' KB';

            const li = document.createElement('li');
            li.className = 'list-group-item py-1 px-2 d-flex align-items-center gap-2 small';
            li.innerHTML = '<i class="bi ' + icono + ' fs-5"></i><span class="flex-grow-1 text-truncate">' + file.name + '</span><span class="text-muted">' + size + '</span>';
            ul.appendChild(li);
        });

        listaDocumentos.appendChild(ul);
    });
});
</script>
@endpush