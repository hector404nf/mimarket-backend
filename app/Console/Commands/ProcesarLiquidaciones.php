<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use App\Services\ComisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcesarLiquidaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liquidaciones:procesar 
                            {--tienda= : ID de tienda específica para procesar}
                            {--dias=30 : Días hacia atrás para considerar comisiones}
                            {--dry-run : Ejecutar sin hacer cambios reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa liquidaciones automáticas para tiendas con comisiones pendientes';

    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        parent::__construct();
        $this->comisionService = $comisionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando procesamiento de liquidaciones...');
        
        $tiendaId = $this->option('tienda');
        $dias = (int) $this->option('dias');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado - No se realizarán cambios reales');
        }

        try {
            // Obtener tiendas a procesar
            $tiendas = $tiendaId 
                ? Tienda::where('id_tienda', $tiendaId)->get()
                : Tienda::where('verificada', true)->get();

            if ($tiendas->isEmpty()) {
                $this->error('❌ No se encontraron tiendas para procesar');
                return Command::FAILURE;
            }

            $this->info("📊 Procesando {$tiendas->count()} tienda(s)...");

            $liquidacionesCreadas = 0;
            $errores = 0;

            foreach ($tiendas as $tienda) {
                $this->line("🏪 Procesando tienda: {$tienda->nombre_tienda} (ID: {$tienda->id_tienda})");

                try {
                    // Verificar si hay comisiones pendientes
                    $comisionesPendientes = $this->comisionService->getComisionesPendientesTienda($tienda->id_tienda);
                    
                    if ($comisionesPendientes->isEmpty()) {
                        $this->line("   ℹ️  No hay comisiones pendientes");
                        continue;
                    }

                    $this->line("   💰 Encontradas {$comisionesPendientes->count()} comisiones pendientes");

                    if (!$dryRun) {
                        // Crear liquidación automática
                        $fechaFin = Carbon::now();
                        $fechaInicio = $fechaFin->copy()->subDays($dias);

                        $liquidacion = $this->comisionService->crearLiquidacionAutomatica(
                            $tienda->id_tienda,
                            $fechaInicio->format('Y-m-d'),
                            $fechaFin->format('Y-m-d'),
                            'Liquidación automática generada por comando'
                        );

                        $this->info("   ✅ Liquidación creada: #{$liquidacion->numero_liquidacion} - Monto: $" . number_format($liquidacion->monto_total, 2));
                        $liquidacionesCreadas++;
                    } else {
                        $montoTotal = $comisionesPendientes->sum('monto_comision');
                        $this->info("   🔍 [DRY-RUN] Se crearía liquidación por: $" . number_format($montoTotal, 2));
                        $liquidacionesCreadas++;
                    }

                } catch (\Exception $e) {
                    $this->error("   ❌ Error procesando tienda {$tienda->id_tienda}: " . $e->getMessage());
                    Log::error("Error en comando liquidaciones:procesar para tienda {$tienda->id_tienda}: " . $e->getMessage());
                    $errores++;
                }
            }

            // Resumen final
            $this->newLine();
            $this->info('📈 RESUMEN DEL PROCESAMIENTO:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Tiendas procesadas', $tiendas->count()],
                    ['Liquidaciones creadas', $liquidacionesCreadas],
                    ['Errores encontrados', $errores],
                    ['Modo', $dryRun ? 'DRY-RUN' : 'PRODUCCIÓN']
                ]
            );

            if ($errores > 0) {
                $this->warn("⚠️  Se encontraron {$errores} errores. Revisa los logs para más detalles.");
                return Command::FAILURE;
            }

            $this->info('🎉 Procesamiento completado exitosamente!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Error crítico: ' . $e->getMessage());
            Log::error('Error crítico en comando liquidaciones:procesar: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Procesar liquidaciones vencidas
     */
    private function procesarLiquidacionesVencidas(): int
    {
        try {
            $liquidacionesVencidas = $this->comisionService->procesarLiquidacionesVencidas();
            
            if ($liquidacionesVencidas > 0) {
                $this->info("   🔄 Procesadas {$liquidacionesVencidas} liquidaciones vencidas");
            }
            
            return $liquidacionesVencidas;
        } catch (\Exception $e) {
            $this->error("   ❌ Error procesando liquidaciones vencidas: " . $e->getMessage());
            return 0;
        }
    }
}
