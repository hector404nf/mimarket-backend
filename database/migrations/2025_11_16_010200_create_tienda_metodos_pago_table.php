<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienda_metodos_pago', function (Blueprint $table) {
            $table->id('id_tienda_metodo');
            $table->unsignedBigInteger('id_tienda');
            $table->string('metodo', 50);
            $table->boolean('activo')->default(true);
            $table->text('configuracion_especial')->nullable();
            $table->decimal('comision_tienda', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->onDelete('cascade');
            $table->unique(['id_tienda', 'metodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienda_metodos_pago');
    }
};