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
        Schema::create('busquedas', function (Blueprint $table) {
            $table->id('id_busqueda');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('termino_busqueda', 255);
            $table->integer('resultados_encontrados')->default(0);
            $table->string('filtros_aplicados', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('busquedas');
    }
};