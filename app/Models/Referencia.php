<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referencia extends Model
{
    /**
     * SoftDeletes implementa el RETIRO del inventario (feature 010 / US2): la
     * referencia sale de toda consulta vigente, pero su fila permanece para que
     * los movimientos, órdenes de salida, transferencias y vaciados en que
     * participó sigan teniendo respaldo.
     *
     * Ojo: a partir de aquí `delete()` sobre una referencia NO borra. Donde se
     * quiera borrado real —eliminar un ingreso completo, consolidar duplicados
     * en Pendientes— hay que usar `forceDelete()` explícitamente.
     */
    use Auditable, SoftDeletes;

    /**
     * Regla de validación para un `referencia_id` que llega del usuario.
     *
     * Un `exists:referencias,id` a secas acepta referencias ya retiradas —la fila
     * sigue ahí— y la operación revienta después con un 404. Esta regla las
     * descarta en la validación, que es donde el usuario puede entender el
     * rechazo. Se define una sola vez porque la usan seis puntos de entrada:
     * ubicación, transferencias (2), novedades, salida y tarja.
     */
    public const REGLA_EXISTE_VIGENTE = 'exists:referencias,id,deleted_at,NULL';

    protected $fillable = [
        'contenedor_id',
        'producto_id',
        'cliente_id',
        'codigo',
        'descripcion',
        'cantidad_inicial',
        'cantidad_actual',
        'unidad_medida',
        'peso',
        'ubicacion_patio_id',
        'fecha_ingreso',
        'fecha_salida',
        'retirado_por',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
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

    /** Quién retiró la referencia del inventario vigente (feature 010). */
    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por');
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

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }
}