<?php

namespace App\Observers;

use App\Models\Design;
use App\Services\Search\SearchService;
use Illuminate\Support\Facades\Log;

/**
 * DesignSearchObserver
 * 
 * Observer que mantiene el índice de búsqueda sincronizado
 * automáticamente cuando se crean, actualizan o eliminan diseños.
 * 
 * @package App\Observers
 */
class DesignSearchObserver
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Handle the Design "created" event.
     */
    public function created(Design $design): void
    {
        $this->indexDesign($design, 'created');
    }

    /**
     * Handle the Design "updated" event.
     */
    public function updated(Design $design): void
    {
        // Solo re-indexar si campos searchables cambiaron
        if ($this->hasSearchableChanges($design)) {
            $this->indexDesign($design, 'updated');
        }
    }

    /**
     * Handle the Design "deleted" event.
     */
    public function deleted(Design $design): void
    {
        try {
            $this->searchService->removeFromIndex($design);
            
            Log::info('Design removed from search index', [
                'design_id' => $design->id,
                'name' => $design->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove design from search index', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Design "restored" event (para SoftDeletes).
     */
    public function restored(Design $design): void
    {
        $this->indexDesign($design, 'restored');
    }

    /**
     * Handle the Design "forceDeleted" event.
     */
    public function forceDeleted(Design $design): void
    {
        try {
            $this->searchService->removeFromIndex($design);
        } catch (\Exception $e) {
            Log::error('Failed to remove force-deleted design from search index', [
                'design_id' => $design->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Indexar diseño con logging.
     */
    private function indexDesign(Design $design, string $event): void
    {
        try {
            // Cargar relaciones necesarias si no están cargadas
            if (!$design->relationLoaded('categories')) {
                $design->load('categories');
            }
            if (!$design->relationLoaded('variants')) {
                $design->load('variants');
            }

            $result = $this->searchService->indexDesign($design);

            if ($result) {
                Log::debug("Design indexed successfully ({$event})", [
                    'design_id' => $design->id,
                    'name' => $design->name,
                ]);
            } else {
                Log::warning("Design indexing returned false ({$event})", [
                    'design_id' => $design->id,
                ]);
            }
        } catch (\Exception $e) {
            // No lanzar excepción para no interrumpir la operación principal
            Log::error("Failed to index design ({$event})", [
                'design_id' => $design->id,
                'name' => $design->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verificar si los campos searchables cambiaron.
     */
    private function hasSearchableChanges(Design $design): bool
    {
        $searchableFields = ['name', 'description', 'is_active'];
        
        foreach ($searchableFields as $field) {
            if ($design->isDirty($field)) {
                return true;
            }
        }

        return false;
    }
}
