@php /** @var \App\Models\ImportPendingRecord $pendiente */ @endphp
<div class="alert alert-info small">
    <strong>{{ $tipoLabel }}</strong> importado en el batch <code>#{{ $pendiente->import_batch_id }}</code>.<br>
    Campos por completar:
    @foreach ($campos as $c)
        <span class="badge bg-warning text-dark">{{ $c }}</span>
    @endforeach
</div>
