<?php

namespace App\Models;

use App\Enums\ImportRowEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRowResult extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'import_batch_id',
        'hoja',
        'fila_excel',
        'estado',
        'tipo',
        'mensaje',
        'referencia_id',
        'contenedor_id',
        'user_cliente_id',
        'payload_original',
    ];

    protected $casts = [
        'estado' => ImportRowEstado::class,
        'payload_original' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(Referencia::class);
    }

    public function contenedor(): BelongsTo
    {
        return $this->belongsTo(Contenedor::class);
    }

    public function userCliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_cliente_id');
    }
}
