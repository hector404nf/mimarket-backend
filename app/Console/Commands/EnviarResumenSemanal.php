<?php

namespace App\Console\Commands;

use App\Models\Comision;
use App\Models\Tienda;
use App\Services\NotificacionComisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarResumenSemanal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comisiones:resumen-semanal {--tienda= : ID específico de tienda}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar resumen semanal de comisiones a las tiendas';

    protected $notificacionService;

    public function __construct(NotificacionComisionService $notificacionService)
    {
        parent::__construct();
        $this->notificacionService = $notificacionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando envío de resúmenes semanales de comisiones...');

        try {
            $tiendaId = $this->option('tienda');
            $fechaInicio = now()->subWeek()->startOfWeek();
            $fechaFin = now()->subWeek()->endOfWeek();

            // Obtener tiendas a procesar
            $tiendas = $tiendaId 
                ? Tienda::where('id_tienda', $tiendaId)->get()
                : Tienda::where('verificada', true)->get();

            if ($tiendas->isEmpty()) {
                $this->warn('⚠️  No se encontraron tiendas para procesar');
                return 0;
            }

            $this->info("📊 Procesando {$tiendas->count()} tienda(s) para el período: {$fechaInicio->format('d/m/Y')} - {$fechaFin->format('d/m/Y')}");

            $resumenesEnviados = 0;
            $errores = 0;

            foreach ($tiendas as $tienda) {
                try {
                    $this->info("🏪 Procesando tienda: {$tienda->nombre_tienda} (ID: {$tienda->id_tienda})");

                    // Obtener comisiones de la semana pasada
                    $comisiones = Comision::where('id_tienda', $tienda->id_tienda)
                        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                        ->get();

                    if ($comisiones->isEmpty()) {
                        $this->line("   ℹ️  No hay comisiones para esta tienda en el período");
                        continue;
                    }

                    $montoAcumulado = $comisiones->sum('monto_comision');
                    $cantidadComisiones = $comisiones->count();

                    // Enviar notificación de resumen
                    $this->notificacionService->notificarComisionesAcumuladas(
                        $tienda, 
                        $montoAcumulado, 
                        $cantidadComisiones
                    );

                    $this->line("   ✅ Resumen enviado: $" . number_format($montoAcumulado, 2) . " en {$cantidadComisiones} comisiones");
                    $resumenesEnviados++;

                } catch (\Exception $e) {
                    $this->error("   ❌ Error procesando tienda {$tienda->id_tienda}: " . $e->getMessage());
                    Log::error("Error enviando resumen semanal a tienda {$tienda->id_tienda}: " . $e->getMessage());
                    $errores++;
                }
            }

            // Mostrar resumen final
            $this->newLine();
            $this->info('📈 RESUMEN DEL ENVÍO:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Tiendas procesadas', $tiendas->count()],
                    ['Resúmenes enviados', $resumenesEnviados],
                    ['Errores encontrados', $errores],
                    ['Período procesado', "{$fechaInicio->format('d/m/Y')} - {$fechaFin->format('d/m/Y')}"]
                ]
            );

            if ($errores === 0) {
                $this->info('🎉 Envío de resúmenes completado exitosamente!');
            } else {
                $this->warn("⚠️  Envío completado con {$errores} errores. Revisa los logs para más detalles.");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error general en el envío de resúmenes: ' . $e->getMessage());
            Log::error('Error general en envío de resúmenes semanales: ' . $e->getMessage());
            return 1;
        }
    }
}
