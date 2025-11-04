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
        // Añadir campos de comisiones a la tabla ordenes
        Schema::table('ordenes', function (Blueprint $table) {
            $table->decimal('comision_total', 10, 2)->default(0)->after('costo_envio');
            $table->boolean('comisiones_calculadas')->default(false)->after('comision_total');
            $table->timestamp('fecha_calculo_comisiones')->nullable()->after('comisiones_calculadas');
        });

        // Añadir campos de comisiones a la tabla detalles_orden
        Schema::table('detalles_orden', function (Blueprint $table) {
            $table->decimal('comision_tienda', 10, 2)->default(0)->after('subtotal');
            $table->decimal('porcentaje_comision', 5, 2)->default(0)->after('comision_tienda');
        });

        // Crear tabla de comisiones para el registro detallado
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id('id_comision');
            $table->unsignedBigInteger('id_orden');
            $table->unsignedBigInteger('id_tienda');
            $table->unsignedBigInteger('id_plan');
            $table->decimal('monto_venta', 10, 2);
            $table->decimal('porcentaje_comision', 5, 2);
            $table->decimal('monto_comision', 10, 2);
            $table->enum('estado', ['pendiente', 'pagada', 'retenida'])->default('pendiente');
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices y claves foráneas
            $table->foreign('id_orden')->references('id_orden')->on('ordenes')->onDelete('cascade');
            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->onDelete('cascade');
            $table->foreign('id_plan')->references('id_plan')->on('planes_tienda')->onDelete('cascade');
            
            $table->index(['id_tienda', 'estado']);
            $table->index(['fecha_vencimiento']);
        });

        // Crear tabla de liquidaciones para agrupar comisiones
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id('id_liquidacion');
            $table->unsignedBigInteger('id_tienda');
            $table->string('numero_liquidacion')->unique();
            $table->decimal('monto_total', 10, 2);
            $table->integer('cantidad_ordenes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['pendiente', 'procesada', 'pagada', 'cancelada'])->default('pendiente');
            $table->timestamp('fecha_procesamiento')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices y claves foráneas
            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->onDelete('cascade');
            $table->index(['id_tienda', 'estado']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });

        // Crear tabla de relación entre comisiones y liquidaciones
        Schema::create('comisiones_liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_comision');
            $table->unsignedBigInteger('id_liquidacion');
            $table->timestamps();

            // Índices y claves foráneas
            $table->foreign('id_comision')->references('id_comision')->on('comisiones')->onDelete('cascade');
            $table->foreign('id_liquidacion')->references('id_liquidacion')->on('liquidaciones')->onDelete('cascade');
            
            $table->unique(['id_comision', 'id_liquidacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar tablas en orden inverso
        Schema::dropIfExists('comisiones_liquidaciones');
        Schema::dropIfExists('liquidaciones');
        Schema::dropIfExists('comisiones');

        // Eliminar campos de comisiones de las tablas existentes
        Schema::table('detalles_orden', function (Blueprint $table) {
            $table->dropColumn(['comision_tienda', 'porcentaje_comision']);
        });

        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn(['comision_total', 'comisiones_calculadas', 'fecha_calculo_comisiones']);
        });
    }
};
