@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Importaciones de inventario</h1>
            <p class="text-muted mb-0">Carga histórica desde Excel — modo validar (dry-run) o importar.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formSubida">
            <i class="bi bi-upload"></i> Nueva importación
        </button>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="collapse mb-4" id="formSubida">
        <div class="card card-body">
            @include('importacion._form_subida')
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Importaciones recientes</strong></div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Archivo</th>
                        <th>Modo</th>
                        <th>Estado</th>
                        <th>Usuario</th>
                        <th>Subido</th>
                        <th>Resultado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td>{{ $batch->id }}</td>
                            <td><span class="text-truncate d-inline-block" style="max-width:280px" title="{{ $batch->archivo_nombre }}">{{ $batch->archivo_nombre }}</span></td>
                            <td><span class="badge bg-{{ $batch->modo === 'validar' ? 'info' : 'warning' }}">{{ $batch->modo }}</span></td>
                            <td><span class="badge bg-secondary">{{ $batch->estado->value }}</span></td>
                            <td>{{ $batch->usuario?->name }}</td>
                            <td>{{ $batch->created_at?->diffForHumans() }}</td>
                            <td>
                                @if ($batch->total_filas !== null)
                                    {{ $batch->importables }} / {{ $batch->total_filas }}
                                    @if ($batch->errores)
                                        <span class="text-danger">· {{ $batch->errores }} err</span>
                                    @endif
                                    @if ($batch->advertencias)
                                        <span class="text-warning">· {{ $batch->advertencias }} adv</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('importaciones.show', $batch) }}">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Sin importaciones todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $batches->links() }}</div>
</div>
@endsection
