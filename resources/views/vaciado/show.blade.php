@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h2><i class="bi bi-box-seam me-2"></i>Orden de Vaciado #{{ $ordenVaciado->id }}</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('vaciado.index') }}">Vaciado</a></li>
            <li class="breadcrumb-item active">Orden #{{ $ordenVaciado->id }}</li>
        </ol>
    </nav>
</div>

<div class="row">
    {{-- Detalle de la Orden --}}
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-info-circle me-1"></i> Detalle de la Orden</h5>
                <span class="badge bg-{{ $ordenVaciado->estado->color() }} fs-6">
                    {{ $ordenVaciado->estado->label() }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Contenedor:</strong> {{ $ordenVaciado->contenedor->numero }}</p>
                        <p><strong>Supervisor:</strong> {{ $ordenVaciado->supervisor->name }}</p>
                        <p><strong>Fecha Programada:</strong> {{ $ordenVaciado->fecha_programada->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha Inicio:</strong> {{ $ordenVaciado->fecha_inicio ? $ordenVaciado->fecha_inicio->format('d/m/Y H:i') : 'Sin iniciar' }}</p>
                        <p><strong>Fecha Fin:</strong> {{ $ordenVaciado->fecha_fin ? $ordenVaciado->fecha_fin->format('d/m/Y H:i') : 'Sin finalizar' }}</p>
                    </div>
                </div>

                @if($ordenVaciado->notas)
                <div class="mt-2">
                    <strong>Notas:</strong>
                    <p class="text-muted">{{ $ordenVaciado->notas }}</p>
                </div>
                @endif

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mt-3">
                    @if($ordenVaciado->estado === \App\Enums\OrdenVaciadoEstado::Programada)
                    <form action="{{ route('vaciado.iniciar', $ordenVaciado) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary"
                                onclick="return confirm('Esta seguro de iniciar el vaciado?')">
                            <i class="bi bi-play-circle me-1"></i> Iniciar Vaciado
                        </button>
                    </form>
                    @endif

                    @if($ordenVaciado->estado === \App\Enums\OrdenVaciadoEstado::EnProceso)
                    <form action="{{ route('vaciado.finalizar', $ordenVaciado) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Esta seguro de finalizar el vaciado?')">
                            <i class="bi bi-check-circle me-1"></i> Finalizar Vaciado
                        </button>
                    </form>
                    @endif

                    @if($ordenVaciado->novedades->count() > 0)
                    <a href="{{ route('vaciado.novedades.pdf', $ordenVaciado) }}" class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF de Novedades
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Novedades --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Novedades ({{ $ordenVaciado->novedades->count() }})</h5>
            </div>
            <div class="card-body">
                @if($ordenVaciado->novedades->count() > 0)
                    @foreach($ordenVaciado->novedades as $novedad)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-{{ $novedad->tipo->color() }} me-2">{{ $novedad->tipo->label() }}</span>
                                <small class="text-muted">
                                    por {{ $novedad->operador->name }} - {{ $novedad->created_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            @if($novedad->referencia)
                            <span class="badge bg-secondary">Ref: {{ $novedad->referencia->codigo }}</span>
                            @endif
                        </div>
                        <p class="mb-2">{{ $novedad->descripcion }}</p>

                        @if($novedad->photos->count() > 0)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($novedad->photos as $photo)
                            <a href="{{ Storage::url($photo->ruta) }}" target="_blank">
                                <img src="{{ Storage::url($photo->ruta) }}"
                                     alt="{{ $photo->nombre }}"
                                     class="rounded"
                                     style="width: 100px; height: 100px; object-fit: cover;">
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                @else
                <p class="text-muted mb-0">No se han registrado novedades.</p>
                @endif
            </div>
        </div>

        {{-- Formulario para agregar novedad --}}
        @if($ordenVaciado->estado === \App\Enums\OrdenVaciadoEstado::EnProceso)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-1"></i> Registrar Novedad</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('vaciado.novedades.store', $ordenVaciado) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">Tipo de Novedad <span class="text-danger">*</span></label>
                            <select class="form-select @error('tipo') is-invalid @enderror"
                                    id="tipo"
                                    name="tipo"
                                    required>
                                <option value="">Seleccione un tipo</option>
                                @foreach(\App\Enums\NovedadTipo::cases() as $tipo)
                                <option value="{{ $tipo->value }}" {{ old('tipo') === $tipo->value ? 'selected' : '' }}>
                                    {{ $tipo->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="referencia_id" class="form-label">Referencia Afectada</label>
                            <select class="form-select @error('referencia_id') is-invalid @enderror"
                                    id="referencia_id"
                                    name="referencia_id">
                                <option value="">Ninguna (opcional)</option>
                                @foreach($referencias as $referencia)
                                <option value="{{ $referencia->id }}"
                                        data-cantidad="{{ $referencia->cantidad_actual }}"
                                        {{ old('referencia_id') == $referencia->id ? 'selected' : '' }}>
                                    {{ $referencia->codigo }} - {{ $referencia->producto->nombre ?? $referencia->descripcion }} (Stock: {{ $referencia->cantidad_actual }})
                                </option>
                                @endforeach
                            </select>
                            @error('referencia_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Cantidad afectada --}}
                    <div class="mb-3" id="campo-cantidad" style="display:none">
                        <label for="cantidad_afectada" class="form-label">
                            Cantidad Afectada
                            <small class="text-muted" id="stock-disponible"></small>
                        </label>
                        <input type="number"
                               class="form-control @error('cantidad_afectada') is-invalid @enderror"
                               id="cantidad_afectada"
                               name="cantidad_afectada"
                               min="1"
                               value="{{ old('cantidad_afectada') }}"
                               placeholder="Ej: 5">
                        <div class="form-text text-warning">Esta cantidad se descontará del inventario de la referencia.</div>
                        @error('cantidad_afectada')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripcion <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                  id="descripcion"
                                  name="descripcion"
                                  rows="3"
                                  placeholder="Describa la novedad encontrada..."
                                  required>{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="fotos" class="form-label">Fotos de Evidencia</label>
                        <input type="file"
                               class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                               id="fotos"
                               name="fotos[]"
                               multiple
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Formatos: JPG, PNG, WEBP. Maximo 5 MB por archivo. (Opcional)</div>
                        @error('fotos')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('fotos.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i> Registrar Novedad
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar Info --}}
    <div class="col-lg-4">
        <div class="card bg-light mb-3">
            <div class="card-body">
                <h6><i class="bi bi-box me-1"></i> Contenedor</h6>
                <p class="mb-1"><strong>Numero:</strong> {{ $ordenVaciado->contenedor->numero }}</p>
                <p class="mb-1">
                    <strong>Estado:</strong>
                    <span class="badge bg-{{ $ordenVaciado->contenedor->estado->color() }}">
                        {{ $ordenVaciado->contenedor->estado->label() }}
                    </span>
                </p>
                <p class="mb-0"><strong>Referencias:</strong> {{ $referencias->count() }}</p>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-1"></i> Instrucciones</h6>
                <ul class="small mb-0">
                    <li>Inicie el vaciado cuando el equipo este listo.</li>
                    <li>Registre cualquier novedad encontrada durante el proceso.</li>
                    <li>Adjunte fotos como evidencia de cada novedad.</li>
                    <li>Finalice el vaciado cuando se haya completado.</li>
                    <li>Descargue el PDF de novedades para documentacion.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const referenciaSelect = document.getElementById('referencia_id');
    const campoCantidad = document.getElementById('campo-cantidad');
    const stockLabel = document.getElementById('stock-disponible');
    const inputCantidad = document.getElementById('cantidad_afectada');

    if (referenciaSelect) {
        referenciaSelect.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            const cantidad = option.dataset.cantidad;

            if (this.value && cantidad !== undefined) {
                campoCantidad.style.display = 'block';
                stockLabel.textContent = '— Stock disponible: ' + cantidad + ' unidades';
                if (inputCantidad) inputCantidad.max = cantidad;
            } else {
                campoCantidad.style.display = 'none';
                stockLabel.textContent = '';
            }
        });

        // Si hay valor previo (old input), mostrar el campo
        if (referenciaSelect.value) {
            referenciaSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush