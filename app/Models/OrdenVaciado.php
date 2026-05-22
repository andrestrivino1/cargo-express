<?php

namespace App\Models;

use App\Enums\OrdenVaciadoEstado;
use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenVaciado extends Model
{
    use HasPhotos;
    protected $table = 'ordenes_vaciado';

    protected $fillable = [
        'contenedor_id',
        'supervisor_id',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'estado' => OrdenVaciadoEstado::class,
            'fecha_programada' => 'date',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }

    public function contenedor(): BelongsTo
    {
        return $this->belongsTo(Contenedor::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function novedades(): HasMany
    {
        return $this->hasMany(Novedad::class);
    }
}