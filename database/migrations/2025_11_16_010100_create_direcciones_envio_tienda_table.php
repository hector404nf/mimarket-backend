<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direcciones_envio_tienda', function (Blueprint $table) {
            $table->id('id_direccion_envio');
            $table->unsignedBigInteger('id_tienda');
            $table->string('nombre', 200);
            $table->decimal('precio_envio', 10, 2);
            $table->integer('minutos_entrega');
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->string('direccion_completa', 500);
            $table->text('zona_cobertura')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones_envio_tienda');
    }
};