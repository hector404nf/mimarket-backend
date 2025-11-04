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
        Schema::create('orden_envios', function (Blueprint $table) {
            $table->id('id_orden_envio');
            $table->unsignedBigInteger('id_orden');
            $table->unsignedBigInteger('id_direccion_envio')->nullable();
            $table->string('nombre_completo', 200)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->timestamps();

            $table->foreign('id_orden')->references('id_orden')->on('ordenes')->onDelete('cascade');
            // FK opcional si existe la tabla direcciones_envio
            $table->foreign('id_direccion_envio')->references('id_direccion')->on('direcciones_envio')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_envios');
    }
};