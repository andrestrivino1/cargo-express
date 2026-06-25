<?php

namespace App\Models;

use App\Enums\MovimientoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public const UPDATED_AT = null;

    protected $fillable = [
        'referencia_id',
        'tipo',
        'cantidad',
        'saldo_resultante',
        'usuario_id',
        'documentable_type',
        'documentable_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => MovimientoTipo::class,
            'created_at' => 'datetime',
        ];
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(Referencia::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
