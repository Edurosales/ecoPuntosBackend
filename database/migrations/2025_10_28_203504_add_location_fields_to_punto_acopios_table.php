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
        Schema::table('punto_acopios', function (Blueprint $table) {
            $table->string('departamento')->nullable()->after('direccion');
            $table->string('provincia')->nullable()->after('departamento');
            $table->string('distrito')->nullable()->after('provincia');
            $table->string('referencia')->nullable()->after('distrito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punto_acopios', function (Blueprint $table) {
            $table->dropColumn(['departamento', 'provincia', 'distrito', 'referencia']);
        });
    }
};
