<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'medidas',
        'calibre',
        'peso',
        'empaque',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'peso' => 'decimal:2',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function referencias(): HasMany
    {
        return $this->hasMany(Referencia::class);
    }
}
