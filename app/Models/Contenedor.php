<?php

namespace App\Models;

use App\Enums\ContenedorEstado;
use App\Traits\HasImportPendingFields;
use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contenedor extends Model
{
    use HasImportPendingFields, HasPhotos;

    protected $table = 'contenedores';

    protected $fillable = [
        'ingreso_id',
        'orden_servicio_id',
        'numero',
        'placa_vehiculo',
        'tipo',
        'bl',
        'tipo_mercancia',
        'estado',
        'fecha_ingreso',
        'fecha_salida',
        'limpieza_registrada',
        'destino_salida',
        'notas_conflicto',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => ContenedorEstado::class,
            'fecha_ingreso' => 'datetime',
            'fecha_salida' => 'datetime',
            'limpieza_registrada' => 'boolean',
        ];
    }

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class);
    }

    public function ordenServicio(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class);
    }

    public function gateEvents(): HasMany
    {
        return $this->hasMany(GateEvent::class);
    }

    public function referencias(): HasMany
    {
        return $this->hasMany(Referencia::class);
    }

    public function ordenesVaciado(): HasMany
    {
        return $this->hasMany(OrdenVaciado::class);
    }

    public function marcarEnPatio(): void
    {
        $this->estado = ContenedorEstado::EnPatio;
        $this->save();
    }

    public function marcarEnVaciado(): void
    {
        $this->estado = ContenedorEstado::EnVaciado;
        $this->save();
    }

    public function marcarVaciadoCompletado(): void
    {
        $this->estado = ContenedorEstado::VaciadoCompletado;
        $this->save();
    }

    public function marcarFueraDePatio(): void
    {
        $this->estado = ContenedorEstado::FueraDePatio;
        $this->save();
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
