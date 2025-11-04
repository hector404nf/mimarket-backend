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
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id('id_tienda');
            $table->foreignId('user_id')->constrained('users');
            $table->string('nombre_tienda', 200);
            $table->text('descripcion')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono_contacto', 20)->nullable();
            $table->string('email_contacto')->nullable();
            $table->decimal('calificacion_promedio', 3, 2)->default(0);
            $table->integer('total_productos')->default(0);
            $table->boolean('verificada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiendas');
    }
};
