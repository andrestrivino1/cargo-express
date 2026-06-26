{{-- Imágenes del BL: galería existente + carga aditiva. Feature 007 / US2. --}}
<div class="card mt-3">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-images me-2"></i>
        <strong>Imágenes del BL</strong>
        <span class="badge bg-secondary ms-2">{{ $ingreso->fotos->count() }}</span>
    </div>
    <div class="card-body">
        @if ($ingreso->fotos->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach ($ingreso->fotos as $foto)
            <a href="{{ $foto->url }}" target="_blank" title="{{ $foto->nombre }}">
                <img src="{{ $foto->url }}" alt="{{ $foto->nombre }}" class="rounded border" style="width:120px;height:120px;object-fit:cover;">
            </a>
            @endforeach
        </div>
        @else
        <p class="text-muted small">Aún no hay imágenes para este BL.</p>
        @endif

        <label class="form-label">Agregar imágenes</label>
        <input type="file"
               class="form-control @error('fotos.*') is-invalid @enderror"
               name="fotos[]"
               multiple
               accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG o WEBP. Máx. 5 MB por imagen. Las imágenes nuevas se agregan a las existentes.</div>
        @error('fotos.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
