<?php

namespace App\Models;

use App\Traits\HasImportPendingFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarja extends Model
{
    use HasImportPendingFields;

    protected $fillable = [
        'orden_cargue_id',
        'despachador_id',
        'fecha_entrega',
        'observaciones',
        'vehiculo',
        'conductor',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'datetime',
        ];
    }

    public function ordenCargue(): BelongsTo
    {
        return $this->belongsTo(OrdenCargue::class);
    }

    public function despachador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachador_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TarjaDetalle::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
