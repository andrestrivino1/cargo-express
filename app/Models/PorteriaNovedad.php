<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PorteriaNovedad extends Model
{
    protected $table = 'porteria_novedades';

    protected $fillable = [
        'portero_id',
        'placa',
        'numero_contenedor',
        'descripcion',
        'reportado_at',
    ];

    protected function casts(): array
    {
        return [
            'reportado_at' => 'datetime',
        ];
    }

    public function portero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portero_id');
    }
}
