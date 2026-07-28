<?php

namespace App\Models;

use App\Enums\PorteriaFotoCategoria;
use App\Traits\HasPhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PorteriaRegistro extends Model
{
    use HasPhotos;

    protected $table = 'porteria_registros';

    protected $fillable = [
        'cita_id',
        'portero_id',
        'llegada_at',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'llegada_at' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function portero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portero_id');
    }

    /**
     * Evidencia de una categoría concreta (vehículo, contenedor, sello, tiquete).
     */
    public function fotoPorCategoria(PorteriaFotoCategoria $categoria): ?Photo
    {
        return $this->photos->firstWhere('categoria', $categoria->value);
    }

    /**
     * Las cuatro evidencias en orden, para recorrerlas en la vista.
     *
     * @return array<string, Photo|null>
     */
    public function evidencias(): array
    {
        $evidencias = [];

        foreach (PorteriaFotoCategoria::cases() as $categoria) {
            $evidencias[$categoria->value] = $this->fotoPorCategoria($categoria);
        }

        return $evidencias;
    }
}
