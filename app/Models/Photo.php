<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'photos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'photoable_type',
        'photoable_id',
        'ruta',
        'nombre',
        'tipo',
        'categoria',
        'mime_type',
        'tamaño',
    ];

    public function esFoto(): bool
    {
        return $this->tipo === 'foto';
    }

    public function esDocumento(): bool
    {
        return $this->tipo === 'documento';
    }

    public function getIconoAttribute(): string
    {
        return match (true) {
            str_contains($this->mime_type ?? '', 'pdf') => 'bi-file-earmark-pdf text-danger',
            str_contains($this->mime_type ?? '', 'word') => 'bi-file-earmark-word text-primary',
            str_contains($this->mime_type ?? '', 'excel') || str_contains($this->mime_type ?? '', 'spreadsheet') => 'bi-file-earmark-excel text-success',
            default => 'bi-file-earmark text-secondary',
        };
    }

    /**
     * Get the parent photoable model.
     */
    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * URL para mostrar/descargar el archivo. Se sirve a través de Laravel
     * (ruta `media`) en lugar de un symlink de storage, que en hosting
     * compartido suele fallar al apuntar fuera de public_html.
     */
    public function getUrlAttribute(): string
    {
        return url('media/'.ltrim($this->ruta, '/'));
    }
}
