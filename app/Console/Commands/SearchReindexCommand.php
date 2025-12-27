<?php

namespace App\Console\Commands;

use App\Services\Search\SearchService;
use Illuminate\Console\Command;

/**
 * SearchReindexCommand
 * 
 * Comando para re-indexar toda la base de datos de búsqueda.
 * Útil después de migraciones o cambios en el esquema de indexación.
 * 
 * Uso: php artisan search:reindex
 * 
 * @package App\Console\Commands
 */
class SearchReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'search:reindex 
                            {--fresh : Eliminar índice existente antes de reindexar}
                            {--model= : Solo reindexar un modelo específico (ej: Design)}';

    /**
     * The console command description.
     */
    protected $description = 'Re-indexar todos los modelos para búsqueda';

    /**
     * Execute the console command.
     */
    public function handle(SearchService $searchService): int
    {
        $this->info('🔍 Iniciando re-indexación de búsqueda...');
        $this->newLine();

        $fresh = $this->option('fresh');
        $modelFilter = $this->option('model');

        if ($fresh) {
            $this->warn('⚠️  Opción --fresh: Se eliminará el índice existente');
            if (!$this->confirm('¿Continuar?')) {
                $this->info('Operación cancelada.');
                return self::FAILURE;
            }
        }

        $startTime = microtime(true);

        try {
            // Crear barra de progreso
            $this->output->write('Contando registros... ');
            $total = \App\Models\Design::count();
            $this->info("✓ {$total} diseños encontrados");
            
            $bar = $this->output->createProgressBar($total);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
            $bar->setMessage('Preparando...');
            $bar->start();

            // Ejecutar re-indexación
            $result = $searchService->reindexAll(function ($processed, $total, $name) use ($bar) {
                $bar->setMessage("Indexando: {$name}");
                $bar->advance();
            });

            $bar->finish();
            $this->newLine(2);

            // Mostrar resultados
            $duration = round(microtime(true) - $startTime, 2);

            $this->info('✅ Re-indexación completada');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total procesados', $result['total']],
                    ['Exitosos', $result['success']],
                    ['Fallidos', $result['failed']],
                    ['Duración', "{$duration} segundos"],
                    ['Velocidad', round($result['total'] / max($duration, 0.01), 1) . ' docs/seg'],
                ]
            );

            if ($result['failed'] > 0) {
                $this->warn("⚠️  {$result['failed']} documentos fallaron. Revisa los logs para más detalles.");
                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error durante re-indexación: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
