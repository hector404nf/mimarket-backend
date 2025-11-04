<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orden;
use App\Models\OrdenEnvio;
use App\Models\DireccionEnvio;

class BackfillOrdenEnviosSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $cities = [
            ['ciudad' => 'Asunción', 'estado' => 'Central', 'codigo_postal' => '1209'],
            ['ciudad' => 'San Lorenzo', 'estado' => 'Central', 'codigo_postal' => '2160'],
            ['ciudad' => 'Luque', 'estado' => 'Central', 'codigo_postal' => '2060'],
            ['ciudad' => 'Lambaré', 'estado' => 'Central', 'codigo_postal' => '2400'],
            ['ciudad' => 'Fernando de la Mora', 'estado' => 'Central', 'codigo_postal' => '2300'],
        ];

        Orden::orderBy('id_orden')
            ->chunk(100, function ($ordenes) use (&$created, &$updated, $cities) {
                foreach ($ordenes as $orden) {
                    $existing = OrdenEnvio::where('id_orden', $orden->id_orden)->first();
                    if ($existing) {
                        $updated++;
                        continue;
                    }

                    // Intentar usar la última dirección del usuario
                    $dir = DireccionEnvio::where('user_id', $orden->user_id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($dir) {
                        // Coordenadas de prueba cercanas al centro de Asunción
                        $lat = -25.2637 + (mt_rand(-3000, 3000) / 100000); // ±0.03
                        $lng = -57.5759 + (mt_rand(-3000, 3000) / 100000); // ±0.03

                        OrdenEnvio::create([
                            'id_orden' => $orden->id_orden,
                            'id_direccion_envio' => $dir->id_direccion,
                            'nombre_completo' => $dir->nombre_completo,
                            'direccion' => $dir->direccion,
                            'ciudad' => $dir->ciudad,
                            'estado' => $dir->estado,
                            'codigo_postal' => $dir->codigo_postal,
                            'pais' => $dir->pais ?? 'Paraguay',
                            'telefono' => $dir->telefono,
                            'latitud' => $lat,
                            'longitud' => $lng,
                        ]);
                        $created++;
                        continue;
                    }

                    // Generar dirección de prueba si el usuario no tiene dirección
                    $pick = $cities[$orden->id_orden % count($cities)];
                    $numero = str_pad((string) ($orden->id_orden % 300 + 1), 3, '0', STR_PAD_LEFT);
                    $telefono = '0981' . str_pad((string) ($orden->id_orden % 10000), 4, '0', STR_PAD_LEFT);

                    // Coordenadas de prueba aleatorias dentro de un rango
                    $lat = -25.3000 + (mt_rand(0, 6000) / 100000); // -25.3000 a -25.2400
                    $lng = -57.6200 + (mt_rand(0, 6000) / 100000); // -57.6200 a -57.5600

                    OrdenEnvio::create([
                        'id_orden' => $orden->id_orden,
                        'nombre_completo' => 'Cliente MiMarket',
                        'direccion' => 'Av. Siempre Viva ' . $numero,
                        'ciudad' => $pick['ciudad'],
                        'estado' => $pick['estado'],
                        'codigo_postal' => $pick['codigo_postal'],
                        'pais' => 'Paraguay',
                        'telefono' => $telefono,
                        'latitud' => $lat,
                        'longitud' => $lng,
                    ]);
                    $created++;
                }
            });

        if (isset($this->command)) {
            $this->command->info("OrdenEnvios creados: {$created}; existentes saltados: {$updated}");
        }
    }
}