<?php

namespace App\Models;

use App\Enums\ImportEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'usuario_id',
        'archivo_nombre',
        'archivo_hash',
        'archivo_path',
        'modo',
        'dry_run',
        'politica_duplicados',
        'fecha_corte',
        'origen',
        'estado',
        'total_filas',
        'importables',
        'errores',
        'advertencias',
        'ignoradas',
        'clientes_autocreados',
        'contenedores_creados',
        'referencias_creadas',
        'despachos_historicos_creados',
        'resumen',
        'started_at',
        'finished_at',
        'error_mensaje',
    ];

    protected $casts = [
        'dry_run' => 'bool',
        'fecha_corte' => 'date',
        'estado' => ImportEstado::class,
        'resumen' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function rowResults(): HasMany
    {
        return $this->hasMany(ImportRowResult::class);
    }

    public function pendingRecords(): HasMany
    {
        return $this->hasMany(ImportPendingRecord::class);
    }
}
