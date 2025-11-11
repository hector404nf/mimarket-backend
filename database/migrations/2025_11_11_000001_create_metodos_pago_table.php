<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id('id_metodo');
            $table->unsignedBigInteger('user_id');
            $table->string('tipo'); // tarjeta | efectivo | transferencia
            $table->string('marca')->nullable();
            $table->string('terminacion', 4)->nullable();
            $table->string('nombre_titular')->nullable();
            $table->unsignedTinyInteger('mes_venc')->nullable();
            $table->unsignedSmallInteger('anio_venc')->nullable();
            $table->string('banco')->nullable();
            $table->boolean('predeterminada')->default(false);
            $table->boolean('activo')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};