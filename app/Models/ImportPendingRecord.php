<?php

namespace App\Models;

use App\Enums\PendingFieldCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImportPendingRecord extends Model
{
    protected $fillable = [
        'pendienteable_type',
        'pendienteable_id',
        'import_batch_id',
        'campos_pendientes',
        'prioridad',
        'completado_at',
        'completado_por_id',
    ];

    protected $casts = [
        'campos_pendientes' => 'array',
        'completado_at' => 'datetime',
        'prioridad' => 'int',
    ];

    public function pendienteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function completadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completado_por_id');
    }

    public function scopeVivos(Builder $query): Builder
    {
        return $query->whereNull('completado_at');
    }

    public function scopeParaTipo(Builder $query, string $morphClass): Builder
    {
        return $query->where('pendienteable_type', $morphClass);
    }

    /**
     * Marca el pendiente como completado. Valida que las claves provistas
     * correspondan al catálogo del tipo polimórfico (Constitución §IV/DRY).
     *
     * @param  array<string, mixed>  $datos
     */
    public function completar(array $datos, User $por): void
    {
        PendingFieldCatalog::validarCampos($this->pendienteable_type, array_keys($datos));

        $this->forceFill([
            'completado_at' => now(),
            'completado_por_id' => $por->getKey(),
        ])->save();
    }
}
