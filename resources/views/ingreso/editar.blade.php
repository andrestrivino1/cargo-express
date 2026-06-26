@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-pencil-square me-2"></i>Editar Ingreso — BL {{ $ingreso->bl }}</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ingreso.index') }}">Ingreso</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ingreso.show', $ingreso) }}">BL {{ $ingreso->bl }}</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

@if ($ingreso->bl_por_confirmar)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Este ingreso fue creado por importación con un <strong>BL provisional</strong> (el número de contenedor).
    Escribe el <strong>BL real</strong> y guarda para confirmarlo.
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('ingreso.update', $ingreso) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">BL <span class="text-danger">*</span></label>
                    <input type="text" name="bl" value="{{ old('bl', $ingreso->bl) }}" class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select name="cliente_id" class="form-select" required>
                        <option value="">— Seleccione —</option>
                        @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id', $ingreso->cliente_id) == $cliente->id)>{{ $cliente->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de ingreso <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', $ingreso->fecha_ingreso?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="form-control" required>
                </div>
            </div>
        </div>
    </div>

    @include('ingreso.partials._referencias')

    @include('ingreso.partials._imagenes')

    @include('ingreso.partials._agregar-referencia')

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar</button>
        <a href="{{ route('ingreso.show', $ingreso) }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
@endsection
