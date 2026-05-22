<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100000_add_pending_fields_to_users_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requiere_cambio_password')->default(false)->after('password');
            $table->boolean('email_placeholder')->default(false)->after('requiere_cambio_password');
            $table->timestamp('password_actualizada_at')->nullable()->after('email_placeholder');
            $table->unsignedBigInteger('import_batch_id_origen')->nullable()->after('password_actualizada_at');

            $table->index('requiere_cambio_password');
            $table->index('import_batch_id_origen');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['requiere_cambio_password']);
            $table->dropIndex(['import_batch_id_origen']);
            $table->dropColumn([
                'requiere_cambio_password',
                'email_placeholder',
                'password_actualizada_at',
                'import_batch_id_origen',
            ]);
        });
    }
};
