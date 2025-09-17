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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('telefono', 20)->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('foto_perfil', 500)->nullable();
            $table->timestamp('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
