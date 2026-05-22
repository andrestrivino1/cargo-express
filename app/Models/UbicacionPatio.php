<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbicacionPatio extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ubicaciones_patio';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'modulo',
        'posicion',
        'descripcion',
        'activa',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * Scope: only active locations.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Get the referencias associated with this ubicacion.
     */
    public function referencias(): HasMany
    {
        return $this->hasMany(Referencia::class);
    }
}