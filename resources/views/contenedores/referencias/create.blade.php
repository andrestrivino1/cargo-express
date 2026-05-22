@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-plus-circle me-2"></i>Registrar Referencias</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('gate-in.index') }}">Ingreso</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('referencias.index', $contenedor) }}">{{ $contenedor->numero }}</a>
            </li>
            <li class="breadcrumb-item active">Agregar Referencias</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Referencias para el Contenedor {{ $contenedor->numero }}</h5>
                <button type="button" class="btn btn-sm btn-success" id="btnAgregarFila">
                    <i class="bi bi-plus-circle me-1"></i> Agregar Fila
                </button>
            </div>
            <div class="card-body">
                <form action="{{ route('referencias.store', $contenedor) }}" method="POST" id="formReferencias">
                    @csrf

                    <div class="table-responsive">
                        <table class="table align-middle" id="tablaReferencias">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 20%;">Producto <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Codigo <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">Descripcion</th>
                                    <th style="width: 12%;">Cantidad <span class="text-danger">*</span></th>
                                    <th style="width: 18%;">Unidad de Medida</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="referenciasBody">
                                <tr class="fila-referencia" data-index="0">
                                    <td class="fila-numero">1</td>
                                    <td>
                                        <select class="form-select form-select-sm select-producto @error('referencias.0.producto_id') is-invalid @enderror"
                                                name="referencias[0][producto_id]"
                                                required>
                                            <option value="">-- Seleccionar --</option>
                                            @foreach($productos as $producto)
                                            <option value="{{ $producto->id }}"
                                                    data-nombre="{{ $producto->nombre }}"
                                                    {{ old('referencias.0.producto_id') == $producto->id ? 'selected' : '' }}>
                                                {{ $producto->nombre }}
                                                @if($producto->medidas || $producto->calibre)
                                                ({{ collect([$producto->medidas, $producto->calibre])->filter()->implode(', ') }})
                                                @endif
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('referencias.0.producto_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm @error('referencias.0.codigo') is-invalid @enderror"
                                               name="referencias[0][codigo]"
                                               value="{{ old('referencias.0.codigo') }}"
                                               placeholder="Codigo"
                                               maxlength="100"
                                               required>
                                        @error('referencias.0.codigo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm input-descripcion @error('referencias.0.descripcion') is-invalid @enderror"
                                               name="referencias[0][descripcion]"
                                               value="{{ old('referencias.0.descripcion') }}"
                                               placeholder="Descripcion"
                                               maxlength="255">
                                        @error('referencias.0.descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number"
                                               class="form-control form-control-sm @error('referencias.0.cantidad') is-invalid @enderror"
                                               name="referencias[0][cantidad]"
                                               value="{{ old('referencias.0.cantidad') }}"
                                               placeholder="Cant."
                                               min="1"
                                               required>
                                        @error('referencias.0.cantidad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm @error('referencias.0.unidad_medida') is-invalid @enderror"
                                               name="referencias[0][unidad_medida]"
                                               value="{{ old('referencias.0.unidad_medida', 'unidades') }}"
                                               placeholder="Unidad"
                                               maxlength="50">
                                        @error('referencias.0.unidad_medida')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger btnEliminarFila" title="Eliminar fila">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Registrar Referencias
                        </button>
                        <a href="{{ route('referencias.index', $contenedor) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-1"></i> Informacion</h6>
                <dl class="small mb-0">
                    <dt>Contenedor</dt>
                    <dd>{{ $contenedor->numero }}</dd>
                    <dt>Cliente</dt>
                    <dd>{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</dd>
                </dl>
                <hr>
                <h6><i class="bi bi-lightbulb me-1"></i> Instrucciones</h6>
                <ul class="small mb-0">
                    <li>Use el boton "Agregar Fila" para ingresar multiples referencias.</li>
                    <li>Cada referencia requiere un codigo unico y una cantidad.</li>
                    <li>Puede eliminar filas con el boton rojo de la derecha.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let filaIndex = {{ old('referencias') ? count(old('referencias')) : 1 }};

    const btnAgregar = document.getElementById('btnAgregarFila');
    const tbody = document.getElementById('referenciasBody');

    btnAgregar.addEventListener('click', function () {
        const fila = document.createElement('tr');
        fila.classList.add('fila-referencia');
        fila.setAttribute('data-index', filaIndex);

        const productosOptions = document.querySelector('.select-producto').innerHTML;

        fila.innerHTML = `
            <td class="fila-numero">${filaIndex + 1}</td>
            <td>
                <select class="form-select form-select-sm select-producto"
                        name="referencias[${filaIndex}][producto_id]"
                        required>
                    ${productosOptions}
                </select>
            </td>
            <td>
                <input type="text"
                       class="form-control form-control-sm"
                       name="referencias[${filaIndex}][codigo]"
                       placeholder="Codigo"
                       maxlength="100"
                       required>
            </td>
            <td>
                <input type="text"
                       class="form-control form-control-sm input-descripcion"
                       name="referencias[${filaIndex}][descripcion]"
                       placeholder="Descripcion"
                       maxlength="255">
            </td>
            <td>
                <input type="number"
                       class="form-control form-control-sm"
                       name="referencias[${filaIndex}][cantidad]"
                       placeholder="Cant."
                       min="1"
                       required>
            </td>
            <td>
                <input type="text"
                       class="form-control form-control-sm"
                       name="referencias[${filaIndex}][unidad_medida]"
                       value="unidades"
                       placeholder="Unidad"
                       maxlength="50">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger btnEliminarFila" title="Eliminar fila">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(fila);
        // Reset the select to default option
        fila.querySelector('.select-producto').selectedIndex = 0;
        filaIndex++;
        actualizarNumeros();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btnEliminarFila');
        if (btn) {
            const filas = tbody.querySelectorAll('.fila-referencia');
            if (filas.length > 1) {
                btn.closest('tr').remove();
                actualizarNumeros();
            } else {
                alert('Debe haber al menos una referencia.');
            }
        }
    });

    function actualizarNumeros() {
        const filas = tbody.querySelectorAll('.fila-referencia');
        filas.forEach(function (fila, index) {
            fila.querySelector('.fila-numero').textContent = index + 1;
        });
    }

    // Auto-fill descripcion when a product is selected
    tbody.addEventListener('change', function (e) {
        if (e.target.classList.contains('select-producto')) {
            const select = e.target;
            const fila = select.closest('tr');
            const descripcionInput = fila.querySelector('.input-descripcion');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.value) {
                descripcionInput.value = selectedOption.getAttribute('data-nombre') || '';
            }
        }
    });
});
</script>
@endpush