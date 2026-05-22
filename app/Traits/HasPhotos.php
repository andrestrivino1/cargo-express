<?php

namespace App\Traits;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasPhotos
{
    /**
     * Get all photos for this model.
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    /**
     * Store multiple photos (images only).
     */
    public function guardarFotos(array $archivos, string $carpeta): void
    {
        foreach ($archivos as $archivo) {
            $ruta = Storage::disk('public')->put($carpeta, $archivo);

            $this->photos()->create([
                'ruta' => $ruta,
                'nombre' => $archivo->getClientOriginalName(),
                'tipo' => 'foto',
                'mime_type' => $archivo->getMimeType(),
                'tamaño' => $archivo->getSize(),
            ]);
        }
    }

    /**
     * Store multiple documents (PDF, Word, Excel, etc.).
     */
    public function guardarDocumentos(array $archivos, string $carpeta): void
    {
        foreach ($archivos as $archivo) {
            $ruta = Storage::disk('public')->put($carpeta, $archivo);

            $this->photos()->create([
                'ruta' => $ruta,
                'nombre' => $archivo->getClientOriginalName(),
                'tipo' => 'documento',
                'mime_type' => $archivo->getMimeType(),
                'tamaño' => $archivo->getSize(),
            ]);
        }
    }

    public function fotos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->where('tipo', 'foto');
    }

    public function documentos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->where('tipo', 'documento');
    }
}
