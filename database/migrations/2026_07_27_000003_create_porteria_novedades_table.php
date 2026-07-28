<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Constancia de un vehículo que se presentó en la puerta sin cita para la
     * fecha actual. No crea ni modifica ninguna cita: es un registro suelto para
     * revisión posterior. Agendar corresponde al rol `citas`.
     */
    public function up(): void
    {
        Schema::create('porteria_novedades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portero_id')->constrained('users');

            // Al menos uno de los dos debe venir; la regla se valida en el FormRequest.
            $table->string('placa', 20)->nullable();
            $table->string('numero_contenedor', 20)->nullable();

            $table->text('descripcion');
            $table->dateTime('reportado_at');

            $table->timestamps();

            $table->index('reportado_at');
            $table->index('placa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('porteria_novedades');
    }
};
