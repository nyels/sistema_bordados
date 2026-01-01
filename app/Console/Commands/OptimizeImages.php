<?php

namespace App\Console\Commands;

use App\Models\Image;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;

class OptimizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize
                            {--all : Optimizar todas las imágenes, incluyendo las ya optimizadas}
                            {--limit= : Limitar el número de imágenes a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera thumbnails optimizados para las imágenes existentes';

    protected ImageOptimizerService $optimizer;

    public function __construct(ImageOptimizerService $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando optimización de imágenes...');
        $this->newLine();

        // Construir query
        $query = Image::query();

        if (!$this->option('all')) {
            $query->where('is_optimized', false)
                  ->orWhereNull('is_optimized');
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $images = $query->get();
        $total = $images->count();

        if ($total === 0) {
            $this->info('No hay imágenes pendientes de optimización.');
            return 0;
        }

        $this->info("Se encontraron {$total} imágenes para optimizar.");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($images as $image) {
            try {
                $result = $this->optimizer->optimizeExistingImage($image);

                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nError en imagen {$image->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Optimización completada:");
        $this->line("  - Exitosas: {$success}");
        $this->line("  - Fallidas: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
