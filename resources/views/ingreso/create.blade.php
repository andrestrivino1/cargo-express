@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Nuevo Ingreso de Mercancía</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ingreso.index') }}">Ingreso</a></li>
            <li class="breadcrumb-item active">Nuevo Ingreso</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ route('ingreso.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header">Datos del contenedor</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">BL <span class="text-danger">*</span></label>
                <input type="text" name="bl" value="{{ old('bl') }}" class="form-control" maxlength="100" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contenedor <span class="text-danger">*</span></label>
                <input type="text" name="numero_contenedor" value="{{ old('numero_contenedor') }}" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de mercancía <span class="text-danger">*</span></label>
                <input type="text" name="tipo_mercancia" value="{{ old('tipo_mercancia') }}" class="form-control" maxlength="100" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                <select name="cliente_id" class="form-select" required>
                    <option value="">— Seleccione —</option>
                    @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Referencias / mercancía</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addReferencia"><i class="bi bi-plus"></i> Agregar referencia</button>
        </div>
        <div class="card-body">
            <div id="referencias">
                <div class="row g-2 mb-2 referencia-row">
                    <div class="col-md-2"><input type="text" name="referencias[0][codigo]" class="form-control" placeholder="Referencia" required></div>
                    <div class="col-md-3"><input type="text" name="referencias[0][descripcion]" class="form-control" placeholder="Descripción" required></div>
                    <div class="col-md-2"><input type="text" name="referencias[0][unidad_medida]" class="form-control" placeholder="Unidad" value="unidades" required></div>
                    <div class="col-md-1"><input type="number" step="0.01" min="0" name="referencias[0][peso]" class="form-control" placeholder="Peso" required></div>
                    <div class="col-md-1"><input type="number" min="1" name="referencias[0][cantidad]" class="form-control" placeholder="Cant." required></div>
                    <div class="col-md-3">
                        <select name="referencias[0][ubicacion_patio_id]" class="form-select" required>
                            <option value="">— Ubicación —</option>
                            @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}">{{ $ubicacion->modulo }} - {{ $ubicacion->posicion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Documentos soporte (obligatorios)</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">BL <span class="text-danger">*</span></label>
                <input type="file" name="documento_bl" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">DIM <span class="text-danger">*</span></label>
                <input type="file" name="documento_dim" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Lista de empaque <span class="text-danger">*</span></label>
                <input type="file" name="documento_lista_empaque" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Registrar ingreso</button>
    <a href="{{ route('ingreso.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

@push('scripts')
<script>
    let idx = 1;
    const ubicaciones = `@foreach ($ubicaciones as $u)<option value="{{ $u->id }}">{{ $u->modulo }} - {{ $u->posicion }}</option>@endforeach`;
    document.getElementById('addReferencia').addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 referencia-row';
        row.innerHTML = `
            <div class="col-md-2"><input type="text" name="referencias[${idx}][codigo]" class="form-control" placeholder="Referencia" required></div>
            <div class="col-md-3"><input type="text" name="referencias[${idx}][descripcion]" class="form-control" placeholder="Descripción" required></div>
            <div class="col-md-2"><input type="text" name="referencias[${idx}][unidad_medida]" class="form-control" placeholder="Unidad" value="unidades" required></div>
            <div class="col-md-1"><input type="number" step="0.01" min="0" name="referencias[${idx}][peso]" class="form-control" placeholder="Peso" required></div>
            <div class="col-md-1"><input type="number" min="1" name="referencias[${idx}][cantidad]" class="form-control" placeholder="Cant." required></div>
            <div class="col-md-2"><select name="referencias[${idx}][ubicacion_patio_id]" class="form-select" required><option value="">— Ubicación —</option>${ubicaciones}</select></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-remove"><i class="bi bi-trash"></i></button></div>`;
        document.getElementById('referencias').appendChild(row);
        idx++;
    });
    document.getElementById('referencias').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove')) e.target.closest('.referencia-row').remove();
    });
</script>
@endpush
@endsection
