<?php

namespace App\Models;

use App\Enums\SolicitudEstado;
use App\Traits\Auditable;
use App\Traits\HasImportPendingFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solicitud extends Model
{
    use Auditable, HasImportPendingFields;

    protected $table = 'solicitudes';

    protected $fillable = [
        'cliente_id',
        'numero_contenedor',
        'naviera',
        'puerto_origen',
        'descripcion',
        'estado',
        'fecha_solicitud',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => SolicitudEstado::class,
            'fecha_solicitud' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function ordenServicio(): HasOne
    {
        return $this->hasOne(OrdenServicio::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}