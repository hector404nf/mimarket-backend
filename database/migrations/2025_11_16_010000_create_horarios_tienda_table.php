<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_tienda', function (Blueprint $table) {
            $table->id('id_horario');
            $table->unsignedBigInteger('id_tienda');
            $table->string('dia_semana', 20);
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->boolean('cerrado')->default(false);
            $table->text('notas_especiales')->nullable();
            $table->timestamps();

            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->onDelete('cascade');
            $table->unique(['id_tienda', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_tienda');
    }
};