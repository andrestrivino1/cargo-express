@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-arrow-right me-2"></i>Nueva Salida de Mercancía</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('salida.index') }}">Salida</a></li>
            <li class="breadcrumb-item active">Nueva Salida</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('salida.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header">Datos de la salida</div>
        <div class="card-body row g-3">
            <div class="col-md-5">
                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                <select name="cliente_id" id="cliente_id" class="form-select" required>
                    <option value="">— Seleccione —</option>
                    @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" data-nit="{{ $cliente->nit }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">NIT del cliente</label>
                <input type="text" name="nit" id="nit" value="{{ old('nit') }}" class="form-control" maxlength="30" placeholder="NIT">
                <div class="form-text">Se guarda en el cliente y aparece en el ODC.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha de salida <span class="text-danger">*</span></label>
                <input type="date" name="fecha_salida" value="{{ old('fecha_salida', now()->format('Y-m-d')) }}" class="form-control" required>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Mercancía por despachar</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Referencia</th><th>Disponible</th><th style="width:140px">Cantidad</th><th></th></tr></thead>
                    <tbody id="detalles"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addDetalle" disabled><i class="bi bi-plus"></i> Agregar referencia</button>
            <small class="text-muted ms-2">Seleccione un cliente para cargar sus referencias con saldo.</small>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Datos del conductor y vehículo</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Nombre del conductor <span class="text-danger">*</span></label>
                <input type="text" name="conductor" value="{{ old('conductor') }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cédula</label>
                <input type="text" name="conductor_cedula" value="{{ old('conductor_cedula') }}" class="form-control" maxlength="20">
            </div>
            <div class="col-md-2">
                <label class="form-label">Placa <span class="text-danger">*</span></label>
                <input type="text" name="placa_vehiculo" value="{{ old('placa_vehiculo') }}" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Transportador <span class="text-danger">*</span></label>
                <input type="text" name="transportador" value="{{ old('transportador') }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Destino <span class="text-danger">*</span></label>
                <input type="text" name="destino" value="{{ old('destino') }}" class="form-control" maxlength="150" required>
            </div>
            <div class="col-12">
                <label class="form-label">Observaciones / novedades</label>
                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Evidencias (obligatorias)</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Foto de la mercancía despachada <span class="text-danger">*</span></label>
                <input type="file" name="foto_mercancia" class="form-control" accept=".jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Foto del conductor <span class="text-danger">*</span></label>
                <input type="file" name="foto_conductor" class="form-control" accept=".jpg,.jpeg,.png" required>
            </div>
        </div>
    </div>

    <button type="submit" id="btnSubmit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Registrar salida y generar ODC</button>
    <a href="{{ route('salida.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

@push('scripts')
<script>
    let idx = 0;
    let referencias = [];
    const tbody = document.getElementById('detalles');
    const addBtn = document.getElementById('addDetalle');
    const baseUrl = "{{ url('salida/cliente') }}";

    document.getElementById('cliente_id').addEventListener('change', async function () {
        tbody.innerHTML = '';
        idx = 0;
        // Precargar el NIT guardado del cliente (editable).
        const nitInput = document.getElementById('nit');
        if (nitInput) nitInput.value = this.selectedOptions[0]?.dataset.nit ?? '';
        if (!this.value) { addBtn.disabled = true; return; }
        const res = await fetch(`${baseUrl}/${this.value}/referencias`, { headers: { 'Accept': 'application/json' } });
        referencias = await res.json();
        addBtn.disabled = referencias.length === 0;
        if (referencias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">El cliente no tiene referencias con saldo disponible.</td></tr>';
        } else {
            addRow();
        }
    });

    function optionsHtml() {
        return referencias.map(r => `<option value="${r.id}" data-saldo="${r.cantidad_actual}">${r.codigo} — ${r.descripcion ?? ''} (${r.contenedor ?? ''})</option>`).join('');
    }

    function addRow() {
        const tr = document.createElement('tr');
        tr.className = 'detalle-row';
        tr.innerHTML = `
            <td><select name="detalles[${idx}][referencia_id]" class="form-select ref-select" required><option value="">— Referencia —</option>${optionsHtml()}</select></td>
            <td class="saldo-cell text-muted">—</td>
            <td><input type="number" min="1" name="detalles[${idx}][cantidad]" class="form-control" required></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove"><i class="bi bi-trash"></i></button></td>`;
        tbody.appendChild(tr);
        idx++;
    }

    // Anti doble-submit: bloquea el botón al enviar para no generar dos ODC.
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registrando...';
    });

    addBtn.addEventListener('click', addRow);
    tbody.addEventListener('click', e => { if (e.target.closest('.btn-remove')) e.target.closest('.detalle-row').remove(); });
    tbody.addEventListener('change', e => {
        if (e.target.classList.contains('ref-select')) {
            const opt = e.target.selectedOptions[0];
            const saldo = opt?.dataset.saldo ?? '—';
            e.target.closest('tr').querySelector('.saldo-cell').textContent = saldo;
            const qty = e.target.closest('tr').querySelector('input[type=number]');
            if (saldo !== '—') qty.max = saldo;
        }
    });
</script>
@endpush
@endsection
