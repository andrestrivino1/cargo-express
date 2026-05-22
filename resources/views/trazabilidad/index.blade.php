@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-search fs-4 me-2 text-primary"></i>
        <h2 class="mb-0">Trazabilidad de Contenedores</h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Buscar Contenedor</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('trazabilidad.index') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label for="numero" class="form-label fw-semibold">Número de Contenedor</label>
                                <input type="text"
                                       class="form-control form-control-lg"
                                       id="numero"
                                       name="numero"
                                       value="{{ $busqueda ?? '' }}"
                                       placeholder="Ej: MSKU1234567"
                                       required
                                       autofocus>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-search me-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>

                    @if(isset($noEncontrado) && $noEncontrado)
                    <div class="alert alert-warning mt-4 mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No se encontró ningún contenedor con el número <strong>{{ $busqueda }}</strong>.
                        Verifique el número e intente nuevamente.
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-box-seam display-1 d-block mb-3"></i>
                    <h5>Consulte el historial completo de un contenedor</h5>
                    <p class="mb-0">
                        Ingrese el número del contenedor para ver su línea de tiempo completa:
                        solicitudes, gate events, vaciados, novedades y ubicaciones.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
