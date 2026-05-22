<?php

namespace App\Models;

use App\Enums\OrdenCargueEstado;
use App\Traits\HasImportPendingFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCargue extends Model
{
    use HasImportPendingFields;

    protected $table = 'ordenes_cargue';

    protected $fillable = [
        'cliente_id',
        'despachador_id',
        'fecha_despacho',
        'estado',
        'notas',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => OrdenCargueEstado::class,
            'fecha_despacho' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function despachador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachador_id');
    }

    public function tarjas(): HasMany
    {
        return $this->hasMany(Tarja::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
