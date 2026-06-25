@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-images me-2"></i>Evidencias fotográficas</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>
            <li class="breadcrumb-item active">Evidencias</li>
        </ol>
    </nav>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrar</button></div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($evidencias as $foto)
    <div class="col-md-3 col-sm-4">
        <div class="card h-100">
            <a href="{{ $foto->url }}" target="_blank"><img src="{{ $foto->url }}" class="card-img-top" style="height:160px;object-fit:cover;"></a>
            <div class="card-body p-2">
                <div class="small text-muted">{{ \App\Enums\DocumentoCategoria::tryFrom($foto->categoria ?? '')?->label() ?? 'Foto' }}</div>
                <div class="small">{{ $foto->created_at?->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-4">Sin evidencias en el rango.</div>
    @endforelse
</div>

<div class="mt-3">{{ $evidencias->withQueryString()->links() }}</div>
@endsection
