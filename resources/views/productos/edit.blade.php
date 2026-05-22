@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Editar Producto</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
            <li class="breadcrumb-item active">Editar Producto</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('productos.update', $producto) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               id="nombre" name="nombre" value="{{ old('nombre', $producto->nombre) }}"
                               maxlength="255" required>
                        @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="medidas" class="form-label">Medidas</label>
                        <input type="text" class="form-control @error('medidas') is-invalid @enderror"
                               id="medidas" name="medidas" value="{{ old('medidas', $producto->medidas) }}"
                               placeholder="Ej: 10x20x30 cm" maxlength="100">
                        @error('medidas')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="calibre" class="form-label">Calibre</label>
                        <input type="text" class="form-control @error('calibre') is-invalid @enderror"
                               id="calibre" name="calibre" value="{{ old('calibre', $producto->calibre) }}"
                               maxlength="50">
                        @error('calibre')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="peso" class="form-label">Peso (kg)</label>
                        <input type="number" class="form-control @error('peso') is-invalid @enderror"
                               id="peso" name="peso" value="{{ old('peso', $producto->peso) }}"
                               step="0.01" min="0">
                        @error('peso')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="empaque" class="form-label">Empaque</label>
                        <input type="text" class="form-control @error('empaque') is-invalid @enderror"
                               id="empaque" name="empaque" value="{{ old('empaque', $producto->empaque) }}"
                               maxlength="100">
                        @error('empaque')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo"
                                   value="1" {{ old('activo', $producto->activo) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Guardar
                        </button>
                        <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
