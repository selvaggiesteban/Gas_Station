<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CheckSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of critical system components (MySQL, Redis, Queues)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando chequeo de salud del sistema...');
        $hasErrors = false;

        // 1. Check MySQL
        try {
            DB::connection()->getPdo();
            $this->info('✅ MySQL: Conectado correctamente.');
        } catch (\Exception $e) {
            $this->error('❌ MySQL: Desconectado o error de credenciales.');
            Log::critical('Health Check: MySQL Connection Failed.', ['error' => $e->getMessage()]);
            $hasErrors = true;
        }

        // 2. Check Redis
        try {
            Redis::connection()->ping();
            $this->info('✅ Redis: Conectado correctamente.');
        } catch (\Exception $e) {
            $this->error('❌ Redis: Desconectado (La telemetría y colas pueden fallar).');
            Log::critical('Health Check: Redis Connection Failed.', ['error' => $e->getMessage()]);
            $hasErrors = true;
        }

        // 3. Check Queues (Asegurar que el Worker no está saturado)
        try {
            $queueSize = Queue::size('default');
            $telemetryQueueSize = Queue::size('telemetry');
            
            $this->info("✅ Colas (Queues): 'default' ($queueSize pendientes), 'telemetry' ($telemetryQueueSize pendientes).");
            
            if ($queueSize > 500 || $telemetryQueueSize > 1000) {
                $this->warn('⚠️ Advertencia: Las colas están acumulando muchos trabajos. Verifica si el Queue Worker está corriendo.');
                Log::warning('Health Check: High Queue Volume.', ['default' => $queueSize, 'telemetry' => $telemetryQueueSize]);
            }
        } catch (\Exception $e) {
            $this->error('❌ Colas: Error al verificar el estado de las colas.');
            $hasErrors = true;
        }

        // Resultado Final
        if ($hasErrors) {
            $this->newLine();
            $this->error('🚨 EL SISTEMA PRESENTA FALLOS CRÍTICOS.');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('🚀 SISTEMA OPERATIVO Y SALUDABLE.');
        return Command::SUCCESS;
    }
}
