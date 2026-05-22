<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transferencia extends Model
{
    protected $fillable = [
        'tipo',
        'usuario_id',
        'referencia_origen_id',
        'referencia_destino_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'cantidad',
        'cliente_origen_id',
        'cliente_destino_id',
        'motivo',
        'autorizacion_cliente',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function referenciaOrigen(): BelongsTo
    {
        return $this->belongsTo(Referencia::class, 'referencia_origen_id');
    }

    public function referenciaDestino(): BelongsTo
    {
        return $this->belongsTo(Referencia::class, 'referencia_destino_id');
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(UbicacionPatio::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(UbicacionPatio::class, 'ubicacion_destino_id');
    }

    public function clienteOrigen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_origen_id');
    }

    public function clienteDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_destino_id');
    }
}
