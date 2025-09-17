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
        Schema::create('productos', function (Blueprint $table) {
            $table->id('id_producto');
            $table->unsignedBigInteger('id_tienda')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_categoria');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('cantidad_stock')->default(0);
            $table->string('estado', 50)->default('activo');
            $table->boolean('destacado')->default(false);
            $table->decimal('peso', 8, 3)->nullable();
            $table->string('dimensiones', 100)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('condicion', 50)->default('nuevo');
            $table->string('tipo_vendedor', 20);
            $table->timestamp('fecha_publicacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('activo')->default(true);
            
            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_categoria')->references('id_categoria')->on('categorias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
