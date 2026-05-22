<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarjaDetalle extends Model
{
    protected $fillable = [
        'tarja_id',
        'referencia_id',
        'cantidad_entregada',
        'ubicacion_origen_id',
    ];

    public function tarja(): BelongsTo
    {
        return $this->belongsTo(Tarja::class);
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(Referencia::class);
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(UbicacionPatio::class, 'ubicacion_origen_id');
    }
}
