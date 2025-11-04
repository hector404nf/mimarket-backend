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
        Schema::table('tiendas', function (Blueprint $table) {
            $table->foreignId('id_plan_actual')->nullable()->constrained('planes_tienda', 'id_plan');
            $table->enum('estado_suscripcion', ['activa', 'vencida', 'suspendida', 'cancelada'])->default('activa');
            $table->date('fecha_vencimiento_plan')->nullable();
            $table->boolean('notificacion_vencimiento_enviada')->default(false);
            $table->integer('dias_gracia_restantes')->default(0);
            $table->json('configuracion_plan')->nullable(); // Configuraciones específicas del plan actual
            $table->timestamp('fecha_ultima_facturacion')->nullable();
            $table->decimal('ingresos_mes_actual', 12, 2)->default(0); // Para calcular comisiones
            $table->decimal('comisiones_pendientes', 12, 2)->default(0);
            
            // Índices para optimizar consultas
            $table->index(['estado_suscripcion', 'fecha_vencimiento_plan']);
            $table->index('id_plan_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropForeign(['id_plan_actual']);
            $table->dropColumn([
                'id_plan_actual',
                'estado_suscripcion',
                'fecha_vencimiento_plan',
                'notificacion_vencimiento_enviada',
                'dias_gracia_restantes',
                'configuracion_plan',
                'fecha_ultima_facturacion',
                'ingresos_mes_actual',
                'comisiones_pendientes'
            ]);
        });
    }
};
