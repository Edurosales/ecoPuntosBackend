<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaccion_puntos', function (Blueprint $table) {
            $table->string('tipo_residuo')->nullable()->after('tipo');
            $table->decimal('cantidad_kg', 8, 2)->nullable()->after('tipo_residuo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaccion_puntos', function (Blueprint $table) {
            $table->dropColumn(['tipo_residuo', 'cantidad_kg']);
        });
    }
};
