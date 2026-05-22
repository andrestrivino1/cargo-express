<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referencia extends Model
{
    protected $fillable = [
        'contenedor_id',
        'producto_id',
        'cliente_id',
        'codigo',
        'descripcion',
        'cantidad_inicial',
        'cantidad_actual',
        'unidad_medida',
        'ubicacion_patio_id',
        'fecha_ingreso',
        'fecha_salida',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_salida' => 'datetime',
        ];
    }

    public function contenedor(): BelongsTo
    {
        return $this->belongsTo(Contenedor::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function ubicacionPatio(): BelongsTo
    {
        return $this->belongsTo(UbicacionPatio::class);
    }

    public function novedades(): HasMany
    {
        return $this->hasMany(Novedad::class);
    }

    public function tarjaDetalles(): HasMany
    {
        return $this->hasMany(TarjaDetalle::class);
    }
}