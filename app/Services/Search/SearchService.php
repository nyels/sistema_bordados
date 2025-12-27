<?php

namespace App\Services\Search;

use App\Contracts\Search\SearchAdapterInterface;
use App\Models\Design;
use App\Models\SearchIndex;
use App\Services\Search\Adapters\MySQLSearchAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

/**
 * SearchService
 * 
 * Servicio orquestador de búsqueda empresarial.
 * Coordina normalización, adaptadores y caché.
 * 
 * Responsabilidades:
 * - Normalizar queries de entrada
 * - Delegar búsqueda al adaptador configurado
 * - Manejar caché de resultados
 * - Indexar/desindexar modelos
 * - Proveer API unificada
 * 
 * @package App\Services\Search
 */
class SearchService
{
    private TextNormalizer $normalizer;
    private SearchAdapterInterface $adapter;
    
    /**
     * TTL del caché de búsqueda en segundos.
     */
    private const CACHE_TTL = 300; // 5 minutos

    /**
     * Prefijo para claves de caché.
     */
    private const CACHE_PREFIX = 'search:';

    public function __construct(
        ?TextNormalizer $normalizer = null,
        ?SearchAdapterInterface $adapter = null
    ) {
        $this->normalizer = $normalizer ?? new TextNormalizer();
        $this->adapter = $adapter ?? new MySQLSearchAdapter();
    }

    /**
     * Buscar diseños con normalización completa.
     *
     * @param string $query Término de búsqueda del usuario
     * @param array $options Opciones adicionales
     * @return Collection Colección de resultados
     */
    public function searchDesigns(string $query, array $options = []): Collection
    {
        $options['model_type'] = Design::class;
        return $this->search($query, $options);
    }

    /**
     * Búsqueda general con normalización.
     *
     * @param string $query Término de búsqueda
     * @param array $options Opciones: model_type, category_ids, limit, use_cache
     * @return Collection
     */
    public function search(string $query, array $options = []): Collection
    {
        $query = trim($query);
        
        if (empty($query)) {
            return collect();
        }

        // Verificar caché si está habilitado
        $useCache = $options['use_cache'] ?? true;
        $cacheKey = $this->generateCacheKey($query, $options);
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Normalizar query
        $normalizedQuery = $this->normalizer->normalize($query, true, false);
        
        Log::debug('SearchService::search', [
            'original' => $query,
            'normalized' => $normalizedQuery,
        ]);

        // Ejecutar búsqueda
        $results = $this->adapter->search($normalizedQuery, $options);

        // Guardar en caché
        if ($useCache && $results->isNotEmpty()) {
            Cache::put($cacheKey, $results, self::CACHE_TTL);
        }

        return $results;
    }

    /**
     * Obtener IDs de modelos que coinciden con la búsqueda.
     * Útil para integrar con queries Eloquent existentes.
     *
     * @param string $query
     * @param string $modelType
     * @param array $options
     * @return array Array de IDs
     */
    public function getMatchingIds(string $query, string $modelType, array $options = []): array
    {
        $options['model_type'] = $modelType;
        $results = $this->search($query, $options);
        
        return $results->pluck('id')->toArray();
    }

    /**
     * Buscar diseños y retornar query builder para encadenar.
     * Permite combinar con filtros existentes.
     *
     * @param string $query
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchDesignsQuery(string $query, array $options = []): \Illuminate\Database\Eloquent\Builder
    {
        $ids = $this->getMatchingIds($query, Design::class, $options);
        
        if (empty($ids)) {
            // Retornar query que no dará resultados
            return Design::whereRaw('1 = 0');
        }

        // Mantener orden por relevancia usando FIELD
        $idsString = implode(',', $ids);
        
        return Design::whereIn('id', $ids)
            ->orderByRaw("FIELD(id, {$idsString})");
    }

    /**
     * Autocompletado de búsqueda.
     *
     * @param string $partial Texto parcial
     * @param int $limit Máximo de sugerencias
     * @return Collection
     */
    public function autocomplete(string $partial, int $limit = 10): Collection
    {
        $partial = trim($partial);
        
        if (mb_strlen($partial) < 2) {
            return collect();
        }

        // Normalizar para autocompletado (sin stemming)
        $normalized = $this->normalizer->normalizeForAutocomplete($partial);
        
        return $this->adapter->suggest($normalized, $limit);
    }

    /**
     * Indexar un modelo Design.
     *
     * @param Design $design
     * @return bool
     */
    public function indexDesign(Design $design): bool
    {
        // Construir texto searchable
        $originalText = $design->name;
        if (!empty($design->description)) {
            $originalText .= "\n" . $design->description;
        }

        // Normalizar
        $normalizedText = $this->normalizer->normalize($originalText, true, true);

        // Construir metadata
        $metadata = [
            'category_ids' => $design->categories->pluck('id')->toArray(),
            'has_variants' => $design->variants->isNotEmpty(),
            'variants_count' => $design->variants->count(),
            'is_active' => $design->is_active,
            'slug' => $design->slug,
            'boost' => $this->calculateBoost($design),
        ];

        // Limpiar caché relacionado
        $this->clearCacheForModel($design);

        return $this->adapter->index(
            $design->id,
            Design::class,
            $originalText,
            $normalizedText,
            $metadata
        );
    }

    /**
     * Indexar múltiples diseños (batch).
     *
     * @param Collection|array $designs
     * @return array ['success' => int, 'failed' => int]
     */
    public function indexDesigns($designs): array
    {
        $success = 0;
        $failed = 0;

        foreach ($designs as $design) {
            if ($this->indexDesign($design)) {
                $success++;
            } else {
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Desindexar un modelo.
     *
     * @param Model $model
     * @return bool
     */
    public function removeFromIndex(Model $model): bool
    {
        $this->clearCacheForModel($model);
        return $this->adapter->remove($model->getKey(), get_class($model));
    }

    /**
     * Re-indexar toda la base de datos de diseños.
     *
     * @param callable|null $progressCallback Callback para reportar progreso
     * @return array
     */
    public function reindexAll(?callable $progressCallback = null): array
    {
        // Limpiar índice actual de diseños
        $this->adapter->flush(Design::class);
        
        // Limpiar todo el caché de búsqueda
        $this->clearAllCache();

        $total = Design::count();
        $processed = 0;
        $success = 0;
        $failed = 0;

        // Procesar en chunks para no agotar memoria
        Design::with(['categories', 'variants'])
            ->chunk(100, function ($designs) use (&$processed, &$success, &$failed, $progressCallback, $total) {
                foreach ($designs as $design) {
                    if ($this->indexDesign($design)) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    $processed++;

                    if ($progressCallback) {
                        $progressCallback($processed, $total, $design->name);
                    }
                }
            });

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'duration' => null, // Se puede agregar timing
        ];
    }

    /**
     * Calcular factor de boost para un diseño.
     * Diseños con más variantes o más recientes tienen más peso.
     *
     * @param Design $design
     * @return float
     */
    private function calculateBoost(Design $design): float
    {
        $boost = 1.0;

        // Más variantes = más relevante
        $variantsCount = $design->variants->count();
        if ($variantsCount > 0) {
            $boost += min($variantsCount * 0.1, 0.5); // Máximo +0.5
        }

        // Diseños activos tienen prioridad
        if ($design->is_active) {
            $boost += 0.2;
        }

        // Diseños recientes tienen ligera ventaja
        $daysSinceCreation = $design->created_at->diffInDays(now());
        if ($daysSinceCreation < 30) {
            $boost += 0.1;
        }

        return round($boost, 2);
    }

    /**
     * Generar clave de caché única para una búsqueda.
     */
    private function generateCacheKey(string $query, array $options): string
    {
        $normalized = $this->normalizer->normalize($query);
        $optionsHash = md5(serialize($options));
        
        return self::CACHE_PREFIX . md5($normalized . $optionsHash);
    }

    /**
     * Limpiar caché relacionado con un modelo.
     */
    private function clearCacheForModel(Model $model): void
    {
        // En producción, usar tags de caché o patrón más sofisticado
        // Por ahora, limpiar todo el caché de búsqueda
        // Cache::tags('search')->flush();
    }

    /**
     * Limpiar todo el caché de búsqueda.
     */
    public function clearAllCache(): void
    {
        // Implementación depende del driver de caché
        // Para Redis/Memcached con tags:
        // Cache::tags('search')->flush();
        
        // Para file/database, usar patrón de prefijo
        // Esta es una implementación simplificada
    }

    /**
     * Obtener estadísticas del índice.
     */
    public function getStats(): array
    {
        return $this->adapter->getStats();
    }

    /**
     * Verificar salud del sistema de búsqueda.
     */
    public function healthCheck(): array
    {
        $adapterHealthy = $this->adapter->isHealthy();
        $stats = $this->adapter->getStats();

        return [
            'healthy' => $adapterHealthy,
            'adapter' => get_class($this->adapter),
            'documents_indexed' => $stats['total_documents'] ?? 0,
            'active_documents' => $stats['active_documents'] ?? 0,
            'last_indexed' => $stats['last_indexed'] ?? null,
        ];
    }

    /**
     * Obtener el normalizador para uso directo.
     */
    public function getNormalizer(): TextNormalizer
    {
        return $this->normalizer;
    }

    /**
     * Obtener el adaptador actual.
     */
    public function getAdapter(): SearchAdapterInterface
    {
        return $this->adapter;
    }
}
