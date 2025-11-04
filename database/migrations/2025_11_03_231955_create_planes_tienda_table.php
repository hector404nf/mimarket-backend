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
        Schema::create('planes_tienda', function (Blueprint $table) {
            $table->id('id_plan');
            $table->string('nombre', 50); // basico, premium, enterprise
            $table->string('nombre_display', 100); // Básico, Premium, Enterprise
            $table->text('descripcion');
            $table->decimal('precio_mensual', 10, 2);
            $table->decimal('precio_anual', 10, 2);
            $table->decimal('comision_porcentaje', 5, 2); // 8%, 5%, 3%
            $table->integer('limite_productos')->nullable(); // null = ilimitado
            $table->integer('limite_imagenes_por_producto')->default(5);
            $table->boolean('analytics_avanzado')->default(false);
            $table->boolean('soporte_prioritario')->default(false);
            $table->boolean('personalizacion_tienda')->default(false);
            $table->boolean('integracion_api')->default(false);
            $table->boolean('promociones_destacadas')->default(false);
            $table->boolean('reportes_detallados')->default(false);
            $table->json('metodos_entrega_incluidos'); // ["estandar", "express", "consolidacion"]
            $table->json('caracteristicas_adicionales')->nullable(); // JSON con features específicas
            $table->boolean('activo')->default(true);
            $table->integer('orden_display')->default(0); // Para ordenar en la UI
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_tienda');
    }
};
