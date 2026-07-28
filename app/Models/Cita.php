<?php

namespace App\Models;

use App\Enums\CitaCondicion;
use App\Enums\CitaEstado;
use App\Enums\TamanoContenedor;
use App\Enums\TipoContenedor;
use App\Support\Normalizador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'ingreso_id',
        'contenedor_id',
        'numero_contenedor',
        'tipo',
        'tamano',
        'condicion',
        'fecha_esperada',
        'estado',
        'conductor_nombre',
        'conductor_cedula',
        'conductor_cedula_original',
        'placa',
        'placa_original',
        'empresa',
        'creado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_esperada' => 'date',
            'estado' => CitaEstado::class,
            'condicion' => CitaCondicion::class,
            'tipo' => TipoContenedor::class,
            'tamano' => TamanoContenedor::class,
        ];
    }

    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class);
    }

    public function contenedor(): BelongsTo
    {
        return $this->belongsTo(Contenedor::class);
    }

    public function registroPorteria(): HasOne
    {
        return $this->hasOne(PorteriaRegistro::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Estado real de la cita en este instante.
     *
     * `Vencida` no se guarda nunca en la columna: una cita que sigue Programada
     * con la fecha esperada ya pasada está vencida por definición. Calcularlo al
     * leer evita depender de una tarea programada (el hosting no tiene cron) y
     * garantiza que el estado nunca queda obsoleto.
     */
    public function estadoEfectivo(): CitaEstado
    {
        if ($this->estado === CitaEstado::Programada && $this->fecha_esperada?->isBefore(today())) {
            return CitaEstado::Vencida;
        }

        return $this->estado;
    }

    /**
     * Una cita atendida es terminal: no se edita ni se cancela. Una vencida sí
     * puede editarse, porque cambiarle la fecha es justamente reprogramarla.
     */
    public function puedeEditarse(): bool
    {
        return $this->estado === CitaEstado::Programada;
    }

    public function estaAtendida(): bool
    {
        return $this->estado === CitaEstado::Atendida;
    }

    public function esDeHoy(): bool
    {
        return $this->fecha_esperada?->isSameDay(today()) ?? false;
    }

    /** Citas cuya fecha esperada es la fecha actual. */
    public function scopeDelDia(Builder $query): Builder
    {
        return $query->whereDate('fecha_esperada', today());
    }

    /** Citas que siguen programadas con la fecha ya pasada. */
    public function scopeVencidas(Builder $query): Builder
    {
        return $query->where('estado', CitaEstado::Programada)
            ->whereDate('fecha_esperada', '<', today());
    }

    /**
     * Busca por placa o número de contenedor, normalizando el término para que
     * "abc 123", "ABC-123" y "ABC123" encuentren lo mismo.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $normalizado = Normalizador::identificador($termino);

        if ($normalizado === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($normalizado) {
            $q->where('placa', 'like', "%{$normalizado}%")
                ->orWhere('numero_contenedor', 'like', "%{$normalizado}%");
        });
    }
}
