<?php

namespace App\Models;

use App\Enums\GateEventTipo;
use App\Traits\Auditable;
use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateEvent extends Model
{
    use Auditable, HasPhotos;

    protected $fillable = [
        'contenedor_id',
        'tipo',
        'usuario_id',
        'hora',
        'estado_fisico',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => GateEventTipo::class,
            'hora' => 'datetime',
        ];
    }

    public function contenedor(): BelongsTo
    {
        return $this->belongsTo(Contenedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}