<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('punto_acopios', function (Blueprint $table) {
            $table->id('id_acopio');
            $table->foreignId('user_id_recolector')
            ->constrained('users', 'id_usuario');
            $table->string('nombre_lugar');
            $table->string('direccion');
            $table->string('ubicacion_gps');
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punto_acopios', function (Blueprint $table) {
            $table->dropForeign(['user_id_recolector']);
        });
        
        Schema::dropIfExists('punto_acopios');
    }
};
