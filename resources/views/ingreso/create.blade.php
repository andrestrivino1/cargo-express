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
<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('ingreso.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header">Datos del BL</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">BL <span class="text-danger">*</span></label>
                <input type="text" name="bl" value="{{ old('bl') }}" class="form-control" maxlength="100" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                <select name="cliente_id" class="form-select" required>
                    <option value="">— Seleccione —</option>
                    @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha de ingreso <span class="text-danger">*</span></label>
                <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-control" required>
                <div class="form-text">Puede ser anterior a hoy (fecha real de llegada).</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Contenedores</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addContenedor"><i class="bi bi-plus"></i> Agregar contenedor</button>
        </div>
        <div class="card-body">
            <div id="contenedores"></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Documentos soporte del BL (obligatorios)</div>
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
    const ubicacionesOpts = `<option value="">— Sin ubicar —</option>@foreach ($ubicaciones as $u)<option value="{{ $u->id }}">{{ $u->modulo }} - {{ $u->posicion }}</option>@endforeach`;
    let ci = 0;
    const cont = document.getElementById('contenedores');

    function refRow(c, r) {
        return `<div class="row g-2 mb-2 referencia-row">
            <div class="col-md-2"><input type="text" name="contenedores[${c}][referencias][${r}][codigo]" class="form-control form-control-sm" placeholder="Referencia" required></div>
            <div class="col-md-3"><input type="text" name="contenedores[${c}][referencias][${r}][descripcion]" class="form-control form-control-sm" placeholder="Descripción" required></div>
            <div class="col-md-1"><input type="text" name="contenedores[${c}][referencias][${r}][unidad_medida]" class="form-control form-control-sm" placeholder="Unidad" value="unidades" required></div>
            <div class="col-md-1"><input type="number" step="0.01" min="0" name="contenedores[${c}][referencias][${r}][peso]" class="form-control form-control-sm" placeholder="Peso"></div>
            <div class="col-md-1"><input type="number" min="1" name="contenedores[${c}][referencias][${r}][cantidad]" class="form-control form-control-sm" placeholder="Cant." required></div>
            <div class="col-md-3"><select name="contenedores[${c}][referencias][${r}][ubicacion_patio_id]" class="form-select form-select-sm">${ubicacionesOpts}</select></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm btn-del-ref"><i class="bi bi-x"></i></button></div>
        </div>`;
    }

    function addContenedor() {
        const c = ci++;
        const card = document.createElement('div');
        card.className = 'border rounded p-3 mb-3 contenedor-block';
        card.dataset.ci = c;
        card.innerHTML = `
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-md-4"><label class="form-label mb-0">Contenedor #${c + 1}</label>
                    <input type="text" name="contenedores[${c}][numero]" class="form-control" placeholder="N° contenedor" maxlength="20" required></div>
                <div class="col-md-4"><input type="text" name="contenedores[${c}][tipo_mercancia]" class="form-control" placeholder="Tipo de mercancía" maxlength="100" required></div>
                <div class="col-md-4 text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-del-cont"><i class="bi bi-trash"></i> Quitar contenedor</button></div>
            </div>
            <div class="small text-muted mb-1">Referencias (ubicación opcional)</div>
            <div class="referencias">${refRow(c, 0)}</div>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-ref"><i class="bi bi-plus"></i> Agregar referencia</button>`;
        cont.appendChild(card);
    }

    document.getElementById('addContenedor').addEventListener('click', addContenedor);
    cont.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-cont')) e.target.closest('.contenedor-block').remove();
        else if (e.target.closest('.btn-del-ref')) e.target.closest('.referencia-row').remove();
        else if (e.target.closest('.btn-add-ref')) {
            const block = e.target.closest('.contenedor-block');
            const c = block.dataset.ci;
            const refs = block.querySelector('.referencias');
            const r = refs.querySelectorAll('.referencia-row').length;
            refs.insertAdjacentHTML('beforeend', refRow(c, r));
        }
    });

    // Inicia con un contenedor
    addContenedor();
</script>
@endpush
@endsection
