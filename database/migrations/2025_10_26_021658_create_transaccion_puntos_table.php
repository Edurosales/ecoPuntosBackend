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
    Schema::create('transaccion_puntos', function (Blueprint $table) {
        $table->id('id_transaccion');
        $table->string('tipo'); // 'ganado' o 'canjeado'
        $table->integer('puntos'); // El valor (+ o -) de la transacción
        $table->dateTime('fecha')->useCurrent();
        $table->string('codigo_reclamacion')->unique()->nullable(); // Único y nulo (para canjes)
        $table->string('status')->default('pendiente_puntos');

        // Nulo si es un 'canjeado'
        $table->foreignId('user_id_recolector')
              ->nullable() 
              ->constrained('users', 'id_usuario');

        // Nulo si es 'pendiente_puntos'
        $table->foreignId('user_id_cliente')
              ->nullable() 
              ->constrained('users', 'id_usuario');
        
        // Nulo si es un 'canjeado' (y no es recojo en tienda)
        $table->foreignId('punto_acopio_id')
              ->nullable()
              ->constrained('punto_acopios', 'id_acopio');

        // Nulo si es un 'ganado'
        $table->foreignId('articulo_id')
              ->nullable()
              ->constrained('articulo_tiendas', 'id_articulo');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaccion_puntos', function (Blueprint $table) {
            $table->dropForeign(['user_id_recolector']);
            $table->dropForeign(['user_id_cliente']);
            $table->dropForeign(['punto_acopio_id']);
            $table->dropForeign(['articulo_id']);
        });
        
        Schema::dropIfExists('transaccion_puntos');
    }
};
