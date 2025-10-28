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
        Schema::create('articulo_tiendas', function (Blueprint $table) {
            $table->id('id_articulo');
            $table->string('nombre');
            $table->text('descripcion');
            $table->integer('stock')->default(0);
            $table->string('imagen_url');
            $table->integer('puntos_requeridos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articulo_tiendas');
    }
};
