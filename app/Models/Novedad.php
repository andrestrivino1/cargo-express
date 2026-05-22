<?php

namespace App\Models;

use App\Enums\NovedadTipo;
use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Novedad extends Model
{
    use HasPhotos;

    protected $table = 'novedades';

    protected $fillable = [
        'orden_vaciado_id',
        'operador_id',
        'tipo',
        'descripcion',
        'referencia_id',
        'cantidad_afectada',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => NovedadTipo::class,
        ];
    }

    public function ordenVaciado(): BelongsTo
    {
        return $this->belongsTo(OrdenVaciado::class);
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(Referencia::class);
    }
}