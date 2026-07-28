{{--
    Formulario compartido por crear y editar.
    El selector de contenedores se puebla desde el ingreso elegido: el número de
    contenedor no se re-digita, viene del ingreso ya registrado.
--}}
@php
    $cita = $cita ?? null;
    $ingresoSeleccionado = old('ingreso_id', $cita?->ingreso_id);
    $contenedorSeleccionado = old('contenedor_id', $cita?->contenedor_id);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Ingreso (BL) <span class="text-danger">*</span></label>
        <select name="ingreso_id" id="ingreso_id" class="form-select @error('ingreso_id') is-invalid @enderror" required>
            <option value="">Selecciona el ingreso…</option>
            @foreach ($ingresos as $ingreso)
            <option value="{{ $ingreso->id }}" @selected((int) $ingresoSeleccionado === $ingreso->id)>
                {{ $ingreso->bl }} — {{ $ingreso->cliente?->name ?? 'sin cliente' }} ({{ $ingreso->fecha_ingreso?->format('d/m/Y') }})
            </option>
            @endforeach
        </select>
        @error('ingreso_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Número de contenedor <span class="text-danger">*</span></label>
        <select name="contenedor_id" id="contenedor_id" class="form-select @error('contenedor_id') is-invalid @enderror" required
                data-seleccionado="{{ $contenedorSeleccionado }}">
            <option value="">Primero selecciona un ingreso…</option>
        </select>
        @error('contenedor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text" id="aviso_cita_existente"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Tipo de contenedor <span class="text-danger">*</span></label>
        <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
            <option value="">Selecciona…</option>
            @foreach ($tipos as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected(old('tipo', $cita?->tipo?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Tamaño <span class="text-danger">*</span></label>
        <select name="tamano" class="form-select @error('tamano') is-invalid @enderror" required>
            <option value="">Selecciona…</option>
            @foreach ($tamanos as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected(old('tamano', $cita?->tamano?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('tamano') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label d-block">Condición <span class="text-danger">*</span></label>
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="condicion" id="condicion_full" value="full" required
                   @checked(old('condicion', $cita?->condicion?->value) === 'full')>
            <label class="btn btn-outline-primary" for="condicion_full"><i class="bi bi-box-fill me-1"></i>Full</label>

            <input type="radio" class="btn-check" name="condicion" id="condicion_vacio" value="vacio"
                   @checked(old('condicion', $cita?->condicion?->value) === 'vacio')>
            <label class="btn btn-outline-secondary" for="condicion_vacio"><i class="bi bi-box me-1"></i>Vacío</label>
        </div>
        @error('condicion') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Fecha posible de llegada <span class="text-danger">*</span></label>
        <input type="date" name="fecha_esperada" class="form-control @error('fecha_esperada') is-invalid @enderror"
               value="{{ old('fecha_esperada', $cita?->fecha_esperada?->toDateString() ?? today()->toDateString()) }}" required>
        @error('fecha_esperada') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Se admite una fecha pasada para regularizar una llegada ya ocurrida.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Nombre del conductor <span class="text-danger">*</span></label>
        <input type="text" name="conductor_nombre" class="form-control @error('conductor_nombre') is-invalid @enderror"
               value="{{ old('conductor_nombre', $cita?->conductor_nombre) }}" maxlength="150" required>
        @error('conductor_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Cédula del conductor <span class="text-danger">*</span></label>
        <input type="text" name="conductor_cedula" class="form-control @error('conductor_cedula') is-invalid @enderror"
               value="{{ old('conductor_cedula', $cita?->conductor_cedula_original ?? $cita?->conductor_cedula) }}" maxlength="40" required>
        @error('conductor_cedula') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Placa del vehículo <span class="text-danger">*</span></label>
        <input type="text" name="placa" class="form-control @error('placa') is-invalid @enderror"
               value="{{ old('placa', $cita?->placa_original ?? $cita?->placa) }}" maxlength="20" required>
        @error('placa') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Nombre de la empresa <span class="text-danger">*</span></label>
        <input type="text" name="empresa" class="form-control @error('empresa') is-invalid @enderror"
               value="{{ old('empresa', $cita?->empresa) }}" maxlength="150" required>
        @error('empresa') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const selectIngreso = document.getElementById('ingreso_id');
    const selectContenedor = document.getElementById('contenedor_id');
    const aviso = document.getElementById('aviso_cita_existente');
    const urlBase = @json(url('citas/ingreso'));

    async function cargarContenedores() {
        const ingresoId = selectIngreso.value;
        aviso.textContent = '';

        if (!ingresoId) {
            selectContenedor.innerHTML = '<option value="">Primero selecciona un ingreso…</option>';
            return;
        }

        selectContenedor.innerHTML = '<option value="">Cargando…</option>';

        try {
            const respuesta = await fetch(`${urlBase}/${ingresoId}/contenedores`, {
                headers: { 'Accept': 'application/json' },
            });
            const contenedores = await respuesta.json();
            const preseleccion = selectContenedor.dataset.seleccionado;

            selectContenedor.innerHTML = '<option value="">Selecciona el contenedor…</option>';

            contenedores.forEach(function (c) {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = c.numero + (c.tipo_mercancia ? ' — ' + c.tipo_mercancia : '');
                option.dataset.tieneCita = c.tiene_cita_programada ? '1' : '0';
                if (String(preseleccion) === String(c.id)) {
                    option.selected = true;
                }
                selectContenedor.appendChild(option);
            });

            mostrarAviso();
        } catch (e) {
            selectContenedor.innerHTML = '<option value="">No se pudieron cargar los contenedores</option>';
        }
    }

    function mostrarAviso() {
        const opcion = selectContenedor.selectedOptions[0];
        if (opcion && opcion.dataset.tieneCita === '1') {
            aviso.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Este contenedor ya tiene una cita programada.</span>';
        } else {
            aviso.textContent = '';
        }
    }

    selectIngreso.addEventListener('change', cargarContenedores);
    selectContenedor.addEventListener('change', mostrarAviso);

    if (selectIngreso.value) {
        cargarContenedores();
    }
})();
</script>
@endpush
