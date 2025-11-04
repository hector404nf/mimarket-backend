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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id('id_reporte');
            $table->foreignId('user_id_reporta')->constrained('users', 'id')->onDelete('cascade');
            $table->string('tipo_contenido', 50);
            $table->unsignedBigInteger('id_contenido');
            $table->string('motivo', 100);
            $table->text('descripcion')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};