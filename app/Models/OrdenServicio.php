<?php

namespace App\Models;

use App\Enums\OrdenServicioEstado;
use App\Traits\HasImportPendingFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrdenServicio extends Model
{
    use HasImportPendingFields;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'solicitud_id',
        'coordinador_id',
        'vehiculo',
        'conductor',
        'conductor_documento',
        'cita_puerto',
        'estado',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => OrdenServicioEstado::class,
            'cita_puerto' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function contenedor(): HasOne
    {
        return $this->hasOne(Contenedor::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}