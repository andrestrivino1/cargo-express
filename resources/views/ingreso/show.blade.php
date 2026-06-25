@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso — Contenedor {{ $contenedor->numero }}</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('ingreso.index') }}">Ingreso</a></li>
            <li class="breadcrumb-item active">{{ $contenedor->numero }}</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Datos del ingreso</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>BL:</strong> {{ $contenedor->bl }}</li>
                <li class="list-group-item"><strong>Contenedor:</strong> {{ $contenedor->numero }}</li>
                <li class="list-group-item"><strong>Tipo de mercancía:</strong> {{ $contenedor->tipo_mercancia }}</li>
                <li class="list-group-item"><strong>Cliente:</strong> {{ $contenedor->referencias->first()?->cliente?->name ?? '—' }}</li>
                <li class="list-group-item"><strong>Fecha ingreso:</strong> {{ $contenedor->fecha_ingreso?->format('d/m/Y H:i') }}</li>
            </ul>
        </div>

        <div class="card mb-3">
            <div class="card-header">Documentos soporte</div>
            <ul class="list-group list-group-flush">
                @forelse ($contenedor->documentos as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi {{ $doc->icono }} me-1"></i> {{ \App\Enums\DocumentoCategoria::tryFrom($doc->categoria)?->label() ?? $doc->nombre }}</span>
                    <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                </li>
                @empty
                <li class="list-group-item text-muted">Sin documentos.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Referencias ingresadas</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Referencia</th><th>Descripción</th><th>Unidad</th><th>Peso</th><th>Cantidad</th><th>Ubicación</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($contenedor->referencias as $ref)
                        <tr>
                            <td>{{ $ref->codigo }}</td>
                            <td>{{ $ref->descripcion }}</td>
                            <td>{{ $ref->unidad_medida }}</td>
                            <td>{{ $ref->peso }}</td>
                            <td>{{ $ref->cantidad_actual }}</td>
                            <td>{{ $ref->ubicacionPatio?->modulo }} - {{ $ref->ubicacionPatio?->posicion }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
