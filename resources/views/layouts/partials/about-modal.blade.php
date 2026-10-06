{{-- "Acerca del sistema": nombre y versión de Cargo Express y el crédito del desarrollador (se abre desde el menú de usuario). --}}
<div class="modal fade about-system" id="modalAcercaSistema" tabindex="-1" aria-labelledby="modalAcercaSistemaLabel" aria-hidden="true" data-about-modal>
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="atrio-stripe"></div>
            <button type="button" class="btn-close position-absolute top-0 end-0 mt-3 me-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            <div class="modal-body text-center py-4">
                <i class="bi bi-box-seam-fill about-system-icon text-primary d-block mb-2"></i>
                <h5 class="fw-bold mb-1" id="modalAcercaSistemaLabel">Cargo Express</h5>
                <div class="text-muted small">Sistema de Trazabilidad de Carga</div>
                <div class="text-muted small" data-app-version>Versión {{ config('app.version') }}</div>
                <hr class="my-3">
                <div class="text-muted small mb-2">Desarrollado por</div>
                <x-atrio-brand :size="30" class="fs-4" />
                <div class="text-muted small mt-2">Tecnología a la medida, desde la base.</div>
            </div>
        </div>
    </div>
</div>
