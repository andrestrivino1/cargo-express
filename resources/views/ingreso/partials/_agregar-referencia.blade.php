{{-- Agregar una referencia nueva a un contenedor del ingreso. Feature 007 / US3. --}}
<div class="card mt-3">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-plus-square me-2"></i>
        <strong>Agregar referencia</strong>
    </div>
    <div class="card-body">
        @if ($ingreso->contenedores->isEmpty())
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Este ingreso no tiene contenedores; no es posible agregar referencias aquí.
        </p>
        @else
        <p class="text-muted small">Opcional. Completa el código para registrar una referencia adicional en el BL.</p>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Contenedor</label>
                <select name="nueva_referencia[contenedor_id]" class="form-select @error('nueva_referencia.contenedor_id') is-invalid @enderror">
                    <option value="">— Seleccione —</option>
                    @foreach ($ingreso->contenedores as $contenedor)
                    <option value="{{ $contenedor->id }}" @selected(old('nueva_referencia.contenedor_id') == $contenedor->id)>{{ $contenedor->numero }}</option>
                    @endforeach
                </select>
                @error('nueva_referencia.contenedor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" name="nueva_referencia[codigo]" value="{{ old('nueva_referencia.codigo') }}" maxlength="100" class="form-control @error('nueva_referencia.codigo') is-invalid @enderror">
                @error('nueva_referencia.codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Descripción</label>
                <input type="text" name="nueva_referencia[descripcion]" value="{{ old('nueva_referencia.descripcion') }}" maxlength="255" class="form-control @error('nueva_referencia.descripcion') is-invalid @enderror">
                @error('nueva_referencia.descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad</label>
                <input type="number" min="1" name="nueva_referencia[cantidad]" value="{{ old('nueva_referencia.cantidad') }}" class="form-control @error('nueva_referencia.cantidad') is-invalid @enderror">
                @error('nueva_referencia.cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Unidad</label>
                <input type="text" name="nueva_referencia[unidad_medida]" value="{{ old('nueva_referencia.unidad_medida', 'unidades') }}" maxlength="50" class="form-control @error('nueva_referencia.unidad_medida') is-invalid @enderror">
                @error('nueva_referencia.unidad_medida')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="nueva_referencia[peso]" value="{{ old('nueva_referencia.peso') }}" class="form-control @error('nueva_referencia.peso') is-invalid @enderror">
                @error('nueva_referencia.peso')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Ubicación en patio</label>
                <select name="nueva_referencia[ubicacion_patio_id]" class="form-select @error('nueva_referencia.ubicacion_patio_id') is-invalid @enderror">
                    <option value="">— Sin ubicar —</option>
                    @foreach ($ubicaciones as $u)
                    <option value="{{ $u->id }}" @selected(old('nueva_referencia.ubicacion_patio_id') == $u->id)>{{ $u->modulo }} - {{ $u->posicion }}</option>
                    @endforeach
                </select>
                @error('nueva_referencia.ubicacion_patio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        @endif
    </div>
</div>
