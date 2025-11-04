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
        Schema::create('suscripciones_tienda', function (Blueprint $table) {
            $table->id('id_suscripcion');
            $table->foreignId('id_tienda')->constrained('tiendas', 'id_tienda')->onDelete('cascade');
            $table->foreignId('id_plan')->constrained('planes_tienda', 'id_plan');
            $table->enum('tipo_facturacion', ['mensual', 'anual']);
            $table->decimal('precio_pagado', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['activa', 'cancelada', 'suspendida', 'vencida'])->default('activa');
            $table->boolean('renovacion_automatica')->default(true);
            $table->string('metodo_pago')->nullable(); // tarjeta, transferencia, etc.
            $table->string('referencia_pago')->nullable(); // ID de transacción externa
            $table->json('configuracion_personalizada')->nullable(); // Configuraciones específicas
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('proximo_cobro')->nullable();
            $table->integer('intentos_cobro_fallidos')->default(0);
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['id_tienda', 'estado']);
            $table->index(['fecha_fin', 'estado']);
            $table->index('proximo_cobro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones_tienda');
    }
};
