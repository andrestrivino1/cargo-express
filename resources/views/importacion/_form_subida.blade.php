<form method="POST" action="{{ route('importaciones.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Archivo Excel (.xlsx)</label>
            <input type="file" name="archivo" accept=".xlsx" required class="form-control @error('archivo') is-invalid @enderror">
            @error('archivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-muted">Máx. {{ (int) (config('importacion.max_file_size_kb') / 1024) }} MB</small>
        </div>
        <div class="col-md-3">
            <label class="form-label">Modo</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="modo" value="validar" id="modoValidar" checked>
                <label class="form-check-label" for="modoValidar">Validar (dry-run)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="modo" value="importar" id="modoImportar">
                <label class="form-check-label" for="modoImportar">Importar</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Fecha de corte</label>
            <input type="date" name="fecha_corte" value="{{ config('importacion.fecha_corte_default') }}" class="form-control">
        </div>
    </div>

    <div id="opcionesImportar" class="row g-3 mt-1" style="display:none">
        <div class="col-md-6">
            <label class="form-label">Política de duplicados</label>
            <select name="politica_duplicados" class="form-select">
                <option value="omitir">Omitir duplicados</option>
                <option value="actualizar_saldo">Actualizar saldo</option>
                <option value="abortar">Abortar si hay duplicados</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="confirmar_clientes_autocreados" value="1" id="confirmar">
                <label class="form-check-label" for="confirmar">
                    Confirmo crear los clientes faltantes con password genérica
                </label>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Subir y procesar</button>
    </div>
</form>

<script>
document.addEventListener('change', function (e) {
    if (e.target.name === 'modo') {
        document.getElementById('opcionesImportar').style.display = e.target.value === 'importar' ? 'flex' : 'none';
    }
});
</script>
