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
        Schema::create('direcciones_envio', function (Blueprint $table) {
            $table->id('id_direccion');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre_completo', 200);
            $table->string('direccion', 255);
            $table->string('ciudad', 100);
            $table->string('estado', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('pais', 100);
            $table->string('telefono', 20)->nullable();
            $table->boolean('predeterminada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones_envio');
    }
};
