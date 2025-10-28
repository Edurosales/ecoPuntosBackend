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
        Schema::create('tipos_residuo', function (Blueprint $table) {
            $table->id('id_tipo');
            $table->string('nombre')->unique(); // Plástico, Papel, Vidrio, Metal, Electrónico, Orgánico, etc.
            $table->string('descripcion')->nullable();
            $table->decimal('puntos_por_kg', 8, 2)->default(10.00); // Cuántos puntos por kg
            $table->string('color_hex')->nullable(); // Para el frontend (#FF5733)
            $table->boolean('activo')->default(true); // Si está activo o no
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_residuo');
    }
};
