<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanesTiendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planes = [
            [
                'nombre' => 'basico',
                'nombre_display' => 'Básico',
                'descripcion' => 'Plan ideal para tiendas que están comenzando. Incluye las funcionalidades esenciales para vender en línea.',
                'precio_mensual' => 0.00,
                'precio_anual' => 0.00,
                'comision_porcentaje' => 8.00,
                'limite_productos' => 50,
                'limite_imagenes_por_producto' => 3,
                'analytics_avanzado' => false,
                'soporte_prioritario' => false,
                'personalizacion_tienda' => false,
                'integracion_api' => false,
                'promociones_destacadas' => false,
                'reportes_detallados' => false,
                'metodos_entrega_incluidos' => json_encode(['estandar']),
                'caracteristicas_adicionales' => json_encode([
                    'soporte_basico' => true,
                    'plantillas_predefinidas' => 3,
                    'almacenamiento_imagenes' => '500MB'
                ]),
                'activo' => true,
                'orden_display' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'premium',
                'nombre_display' => 'Premium',
                'descripcion' => 'Plan completo para tiendas establecidas que buscan crecer. Incluye herramientas avanzadas y menor comisión.',
                'precio_mensual' => 150000.00, // 150,000 PYG
                'precio_anual' => 1500000.00, // 1,500,000 PYG (2 meses gratis)
                'comision_porcentaje' => 5.00,
                'limite_productos' => 500,
                'limite_imagenes_por_producto' => 8,
                'analytics_avanzado' => true,
                'soporte_prioritario' => true,
                'personalizacion_tienda' => true,
                'integracion_api' => false,
                'promociones_destacadas' => true,
                'reportes_detallados' => true,
                'metodos_entrega_incluidos' => json_encode(['estandar', 'express']),
                'caracteristicas_adicionales' => json_encode([
                    'soporte_prioritario' => true,
                    'plantillas_premium' => 10,
                    'almacenamiento_imagenes' => '5GB',
                    'descuentos_promocionales' => true,
                    'estadisticas_detalladas' => true
                ]),
                'activo' => true,
                'orden_display' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'enterprise',
                'nombre_display' => 'Enterprise',
                'descripcion' => 'Plan empresarial para grandes tiendas. Comisión mínima, productos ilimitados y todas las funcionalidades premium.',
                'precio_mensual' => 350000.00, // 350,000 PYG
                'precio_anual' => 3500000.00, // 3,500,000 PYG (2 meses gratis)
                'comision_porcentaje' => 3.00,
                'limite_productos' => null, // Ilimitado
                'limite_imagenes_por_producto' => 15,
                'analytics_avanzado' => true,
                'soporte_prioritario' => true,
                'personalizacion_tienda' => true,
                'integracion_api' => true,
                'promociones_destacadas' => true,
                'reportes_detallados' => true,
                'metodos_entrega_incluidos' => json_encode(['estandar', 'express', 'consolidacion']),
                'caracteristicas_adicionales' => json_encode([
                    'soporte_dedicado' => true,
                    'plantillas_ilimitadas' => true,
                    'almacenamiento_imagenes' => 'Ilimitado',
                    'api_completa' => true,
                    'integraciones_terceros' => true,
                    'reportes_personalizados' => true,
                    'manager_cuenta_dedicado' => true
                ]),
                'activo' => true,
                'orden_display' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('planes_tienda')->insert($planes);
    }
}
