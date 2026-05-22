@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Pendientes por completar</h1>
    <p class="text-muted">Registros importados desde Excel que requieren campos manuales antes de operar.</p>

    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="tipo" class="form-select" onchange="this.form.submit()">
                <option value="">— Todos los tipos —</option>
                @foreach ($tipos as $slug => $label)
                    <option value="{{ $slug }}" @selected(request('tipo') === $slug)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="import_batch_id" class="form-control" placeholder="ID de batch" value="{{ request('import_batch_id') }}">
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary">Filtrar</button></div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Identificador</th>
                        <th>Campos pendientes</th>
                        <th>Batch</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pendientes as $p)
                    @php
                        $modelo = $p->pendienteable;
                        $tipoSlug = collect([
                            \App\Models\Contenedor::class => 'contenedor',
                            \App\Models\OrdenServicio::class => 'orden-servicio',
                            \App\Models\Solicitud::class => 'solicitud',
                            \App\Models\Tarja::class => 'tarja',
                            \App\Models\OrdenCargue::class => 'orden-cargue',
                        ])->get($p->pendienteable_type, 'desconocido');
                        $label = $tipos[$tipoSlug] ?? $p->pendienteable_type;
                        $idFuncional = match (true) {
                            $modelo instanceof \App\Models\Contenedor => $modelo->numero,
                            $modelo instanceof \App\Models\OrdenServicio => '#'.$modelo->id,
                            $modelo instanceof \App\Models\Solicitud => $modelo->numero_contenedor,
                            $modelo instanceof \App\Models\Tarja => 'Tarja #'.$modelo->id.' · '.$modelo->fecha_entrega?->format('Y-m-d'),
                            $modelo instanceof \App\Models\OrdenCargue => '#'.$modelo->id.' · '.$modelo->fecha_despacho?->format('Y-m-d'),
                            default => '—',
                        };
                    @endphp
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td><span class="badge bg-info text-dark">{{ $label }}</span></td>
                        <td>{{ $idFuncional }}</td>
                        <td>
                            @foreach (($p->campos_pendientes ?? []) as $campo)
                                <span class="badge bg-warning text-dark me-1">{{ $campo }}</span>
                            @endforeach
                        </td>
                        <td>#{{ $p->import_batch_id }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('pendientes.editar', ['type' => $tipoSlug, 'id' => $modelo?->id]) }}">Completar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay pendientes vivos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $pendientes->links() }}</div>
</div>
@endsection
