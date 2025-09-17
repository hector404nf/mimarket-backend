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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icono')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamp('fecha_creacion')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
