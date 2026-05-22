<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Documento extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'solicitud_id',
        'nombre',
        'ruta',
        'tipo_mime',
        'tamaño',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta);
    }
}