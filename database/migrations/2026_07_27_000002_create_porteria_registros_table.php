<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Confirmación física de que una cita se materializó en la puerta.
     *
     * `cita_id` es UNIQUE: es la garantía de base de datos de que una cita no se
     * puede confirmar dos veces, incluso ante envíos concurrentes. Más fuerte que
     * una verificación en aplicación y suficiente para este caso, porque el
     * registro está anclado a una cita que ya existe (no genera consecutivos).
     */
    public function up(): void
    {
        Schema::create('porteria_registros', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('portero_id')->constrained('users');

            $table->dateTime('llegada_at');
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('portero_id');
            $table->index('llegada_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('porteria_registros');
    }
};
