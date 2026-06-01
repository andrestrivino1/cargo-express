{{--
    Parcial reutilizable: historial de auditoría de un registro.
    Uso: @include('components.historial-auditoria', ['registro' => $modelo])
    Requiere que el modelo use el trait App\Traits\Auditable.
--}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Historial de cambios</h5>
    </div>
    <div class="card-body">
        @php($entradas = $registro->cambiosAuditoria)
        @if($entradas->isEmpty())
            <p class="text-muted mb-0">Sin modificaciones registradas.</p>
        @else
            <ul class="list-group list-group-flush">
                @foreach($entradas as $entrada)
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $entrada->usuario?->name ?? 'Usuario eliminado' }}</strong>
                            <span class="text-muted small">{{ $entrada->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <ul class="mb-0 mt-1 small">
                            @foreach($entrada->cambios as $campo => $valores)
                                <li>
                                    <strong>{{ $campo }}:</strong>
                                    <span class="text-danger">{{ $valores['anterior'] ?? '—' }}</span>
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <span class="text-success">{{ $valores['nuevo'] ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
