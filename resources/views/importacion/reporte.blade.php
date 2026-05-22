@extends('layouts.app')

@section('content')
<div class="container py-4" data-batch-id="{{ $batch->id }}" data-batch-estado="{{ $batch->estado->value }}">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3">Importación #{{ $batch->id }}</h1>
            <p class="text-muted mb-0">{{ $batch->archivo_nombre }} · {{ $batch->modo }} · por {{ $batch->usuario?->name }}</p>
        </div>
        <div>
            <a class="btn btn-outline-secondary" href="{{ route('importaciones.index') }}">← Volver</a>
        </div>
    </div>

    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Estado</div>
                <div class="h4 mb-0"><span class="badge bg-secondary" id="estadoBadge">{{ $batch->estado->value }}</span></div>
            </div></div>
        </div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total filas</div><div class="h4 mb-0">{{ $batch->total_filas ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card text-success"><div class="card-body"><div class="text-muted small">Importables</div><div class="h4 mb-0">{{ $batch->importables ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card text-danger"><div class="card-body"><div class="text-muted small">Errores</div><div class="h4 mb-0">{{ $batch->errores ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card text-warning"><div class="card-body"><div class="text-muted small">Advertencias</div><div class="h4 mb-0">{{ $batch->advertencias ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Hojas ignoradas</div><div class="h4 mb-0">{{ $batch->ignoradas ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Clientes a auto-crear</div><div class="h4 mb-0">{{ $batch->clientes_autocreados ?? '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Hash</div><div class="small text-truncate" title="{{ $batch->archivo_hash }}">{{ Str::limit($batch->archivo_hash, 16, '…') }}</div></div></div></div>
    </div>

    @if ($batch->estado->value === 'fallido')
        <div class="alert alert-danger"><strong>La importación falló:</strong> {{ $batch->error_mensaje }}</div>
    @endif

    @if (in_array($batch->estado->value, ['pendiente','procesando']))
        <div class="alert alert-info">
            La importación está en curso. La página se actualiza automáticamente.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card"><div class="card-header"><strong>Resultado por hoja</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Hoja</th><th>Estado</th><th class="text-end">Filas</th></tr></thead>
                        <tbody>
                            @foreach ($hojas as $hoja => $estados)
                                @foreach ($estados as $e)
                                    <tr>
                                        <td>{{ $hoja }}</td>
                                        <td><span class="badge bg-secondary">{{ $e->estado }}</span></td>
                                        <td class="text-end">{{ $e->total }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header"><strong>Clientes a auto-crear</strong></div>
                <div class="card-body">
                    @forelse ($clientes as $nombre)
                        <span class="badge bg-info text-dark me-1">{{ $nombre }}</span>
                    @empty
                        <span class="text-muted">No hay clientes nuevos.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($errores->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between"><strong>Errores (primeros 50)</strong>
                <a class="btn btn-sm btn-outline-danger" href="{{ route('importaciones.errores', $batch) }}">Descargar Excel completo</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Hoja</th><th>Fila</th><th>Tipo</th><th>Mensaje</th></tr></thead>
                    <tbody>
                        @foreach ($errores as $err)
                            <tr><td>{{ $err->hoja }}</td><td>{{ $err->fila_excel }}</td><td><code>{{ $err->tipo }}</code></td><td>{{ $err->mensaje }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('importaciones.reporte.xlsx', $batch) }}">Reporte (Excel)</a>
        <a class="btn btn-outline-primary" href="{{ route('importaciones.reporte.pdf', $batch) }}">Reporte (PDF)</a>
        @if ($batch->estado->value === 'pendiente')
            <form method="POST" action="{{ route('importaciones.cancelar', $batch) }}">@csrf
                <button class="btn btn-outline-danger" type="submit">Cancelar</button>
            </form>
        @endif
    </div>
</div>

<script>
(function () {
    const el = document.querySelector('[data-batch-id]');
    if (!el) return;
    const estado = el.dataset.batchEstado;
    if (estado !== 'pendiente' && estado !== 'procesando') return;

    setInterval(async () => {
        try {
            const r = await fetch(window.location.pathname, {headers: {Accept: 'application/json'}});
            if (!r.ok) return;
            const data = await r.json();
            if (data.estado !== estado) {
                window.location.reload();
            }
        } catch (e) {}
    }, 3000);
})();
</script>
@endsection
