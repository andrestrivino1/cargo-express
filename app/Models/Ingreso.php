<?php

namespace App\Models;

use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingreso extends Model
{
    use HasPhotos;

    protected $table = 'ingresos';

    protected $fillable = [
        'bl',
        'bl_por_confirmar',
        'cliente_id',
        'fecha_ingreso',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'bl_por_confirmar' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function contenedores(): HasMany
    {
        return $this->hasMany(Contenedor::class);
    }
}
