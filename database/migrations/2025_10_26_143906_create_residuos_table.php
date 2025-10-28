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
        Schema::create('residuos', function (Blueprint $table) {
            $table->id('id_residuo');
            $table->string('tipo_residuo'); // Plástico, Papel, Vidrio, Metal, Electrónico, etc.
            $table->decimal('cantidad_kg', 8, 2); // Peso del residuo en kg
            $table->integer('puntos_otorgados'); // Puntos generados por este residuo
            $table->dateTime('fecha_registro')->useCurrent();
            
            // Foreign key al recolector que registró el residuo
            $table->foreignId('user_id_recolector')
                  ->constrained('users', 'id_usuario')
                  ->onDelete('cascade');
            
            // Foreign key al punto de acopio donde se registró
            $table->foreignId('punto_acopio_id')
                  ->constrained('punto_acopios', 'id_acopio')
                  ->onDelete('cascade');
            
            // Código QR único para que el cliente reclame los puntos
            $table->string('codigo_qr')->unique();
            
            // Estado: 'disponible', 'reclamado'
            $table->string('estado')->default('disponible');
            
            // Foreign key al cliente que reclamó (nullable hasta que se reclame)
            $table->foreignId('user_id_cliente')
                  ->nullable()
                  ->constrained('users', 'id_usuario')
                  ->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residuos', function (Blueprint $table) {
            $table->dropForeign(['user_id_recolector']);
            $table->dropForeign(['punto_acopio_id']);
            $table->dropForeign(['user_id_cliente']);
        });
        
        Schema::dropIfExists('residuos');
    }
};
