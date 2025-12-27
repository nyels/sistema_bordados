<?php

namespace App\Services\Search\Adapters;

use App\Contracts\Search\SearchAdapterInterface;
use App\Models\SearchIndex;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MySQLSearchAdapter
 * 
 * Implementación del adaptador de búsqueda usando MySQL FULLTEXT.
 * Fase 1 del roadmap de búsqueda empresarial.
 * 
 * Estrategia:
 * 1. Búsqueda primaria en stemmed_tokens (FULLTEXT BOOLEAN MODE)
 * 2. Fallback a normalized_title/content si no hay resultados
 * 3. Último recurso: LIKE con comodines
 * 
 * @package App\Services\Search\Adapters
 */
class MySQLSearchAdapter implements SearchAdapterInterface
{
    /**
     * Umbral mínimo de resultados antes de intentar fallback.
     */
    private const MIN_RESULTS_THRESHOLD = 1;

    /**
     * Límite por defecto de resultados.
     */
    private const DEFAULT_LIMIT = 50;

    /**
     * {@inheritdoc}
     */
    public function search(string $normalizedQuery, array $options = []): Collection
    {
        $modelType = $options['model_type'] ?? null;
        $categoryIds = $options['category_ids'] ?? [];
        $limit = $options['limit'] ?? self::DEFAULT_LIMIT;
        $offset = $options['offset'] ?? 0;
        $onlyActive = $options['only_active'] ?? true;

        try {
            // Construir query base
            $query = SearchIndex::query();

            if ($onlyActive) {
                $query->active();
            }

            if ($modelType) {
                $query->ofType($modelType);
            }

            if (!empty($categoryIds)) {
                $query->inCategories($categoryIds);
            }

            // Estrategia de búsqueda en cascada
            $results = $this->executeSearchStrategy($query, $normalizedQuery, $limit, $offset);

            return $results;

        } catch (\Exception $e) {
            Log::error('MySQLSearchAdapter::search error', [
                'query' => $normalizedQuery,
                'error' => $e->getMessage(),
            ]);
            
            return collect();
        }
    }

    /**
     * Ejecutar estrategia de búsqueda en cascada.
     */
    private function executeSearchStrategy($baseQuery, string $query, int $limit, int $offset): Collection
    {
        // Preparar términos para FULLTEXT BOOLEAN MODE
        $booleanTerms = $this->prepareBooleanTerms($query);
        
        // 1. Intento principal: FULLTEXT en stemmed_tokens
        $results = (clone $baseQuery)
            ->searchStemmed($booleanTerms)
            ->orderByRelevance($booleanTerms)
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($results->count() >= self::MIN_RESULTS_THRESHOLD) {
            return $this->formatResults($results, $query);
        }

        // 2. Fallback: FULLTEXT en texto normalizado
        $results = (clone $baseQuery)
            ->searchNormalized($booleanTerms)
            ->orderByRelevance($booleanTerms)
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($results->count() >= self::MIN_RESULTS_THRESHOLD) {
            return $this->formatResults($results, $query);
        }

        // 3. Último recurso: LIKE (más lento pero más permisivo)
        $likeTerms = explode(' ', $query);
        $results = (clone $baseQuery)
            ->where(function ($q) use ($likeTerms) {
                foreach ($likeTerms as $term) {
                    if (strlen($term) >= 2) {
                        $q->orWhere('stemmed_tokens', 'LIKE', '%' . $term . '%')
                          ->orWhere('normalized_title', 'LIKE', '%' . $term . '%');
                    }
                }
            })
            ->orderByDesc('boost')
            ->orderByDesc('updated_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $this->formatResults($results, $query);
    }

    /**
     * Preparar términos para FULLTEXT BOOLEAN MODE.
     * Agrega + para requerir término y * para prefijo.
     */
    private function prepareBooleanTerms(string $query): string
    {
        $terms = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($terms)) {
            return '';
        }

        // Cada término: +término* (debe existir, permite prefijos)
        return implode(' ', array_map(function ($term) {
            // Escapar caracteres especiales de FULLTEXT
            $term = preg_replace('/[+\-><()~*"@]+/', '', $term);
            return strlen($term) >= 2 ? '+' . $term . '*' : '';
        }, $terms));
    }

    /**
     * Formatear resultados con scores.
     */
    private function formatResults(Collection $results, string $query): Collection
    {
        return $results->map(function ($item) use ($query) {
            return [
                'id' => $item->searchable_id,
                'type' => $item->searchable_type,
                'title' => $item->original_title,
                'content' => $item->original_content,
                'excerpt' => $item->getHighlightedExcerpt($query),
                'score' => $item->relevance_score ?? $item->boost,
                'metadata' => $item->metadata,
                'index_id' => $item->id,
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function index(
        int $modelId,
        string $modelType,
        string $originalText,
        string $normalizedText,
        array $metadata = []
    ): bool {
        try {
            // Separar título y contenido si es posible
            $parts = $this->splitTitleContent($originalText);
            $normalizedParts = $this->splitTitleContent($normalizedText);

            SearchIndex::updateOrCreate(
                [
                    'searchable_type' => $modelType,
                    'searchable_id' => $modelId,
                ],
                [
                    'original_title' => $parts['title'],
                    'original_content' => $parts['content'],
                    'normalized_title' => $normalizedParts['title'],
                    'normalized_content' => $normalizedParts['content'],
                    'stemmed_tokens' => $normalizedText, // Ya viene stemmed
                    'metadata' => $metadata,
                    'is_active' => true,
                    'boost' => $metadata['boost'] ?? 1.0,
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error('MySQLSearchAdapter::index error', [
                'model_id' => $modelId,
                'model_type' => $modelType,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Separar texto en título y contenido.
     */
    private function splitTitleContent(string $text): array
    {
        // Si hay salto de línea, la primera línea es el título
        if (strpos($text, "\n") !== false) {
            $parts = explode("\n", $text, 2);
            return [
                'title' => trim($parts[0]),
                'content' => trim($parts[1] ?? ''),
            ];
        }

        // Si es corto (< 100 chars), todo es título
        if (mb_strlen($text) < 100) {
            return [
                'title' => $text,
                'content' => null,
            ];
        }

        // Si es largo, los primeros 100 chars son título
        return [
            'title' => mb_substr($text, 0, 100),
            'content' => $text,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function remove(int $modelId, string $modelType): bool
    {
        try {
            return SearchIndex::where('searchable_type', $modelType)
                ->where('searchable_id', $modelId)
                ->delete() > 0;
        } catch (\Exception $e) {
            Log::error('MySQLSearchAdapter::remove error', [
                'model_id' => $modelId,
                'model_type' => $modelType,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(
        int $modelId,
        string $modelType,
        string $originalText,
        string $normalizedText,
        array $metadata = []
    ): bool {
        // Update es igual que index gracias a updateOrCreate
        return $this->index($modelId, $modelType, $originalText, $normalizedText, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(?string $modelType = null): bool
    {
        try {
            $query = SearchIndex::query();
            
            if ($modelType) {
                $query->where('searchable_type', $modelType);
            }

            return $query->delete() >= 0;

        } catch (\Exception $e) {
            Log::error('MySQLSearchAdapter::flush error', [
                'model_type' => $modelType,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function suggest(string $partialQuery, int $limit = 5): Collection
    {
        if (mb_strlen($partialQuery) < 2) {
            return collect();
        }

        try {
            $results = SearchIndex::query()
                ->active()
                ->where('normalized_title', 'LIKE', $partialQuery . '%')
                ->orWhere('normalized_title', 'LIKE', '% ' . $partialQuery . '%')
                ->select(['searchable_id', 'searchable_type', 'original_title', 'metadata'])
                ->distinct()
                ->orderByDesc('boost')
                ->limit($limit)
                ->get();

            return $results->map(fn($item) => [
                'id' => $item->searchable_id,
                'type' => $item->searchable_type,
                'title' => $item->original_title,
                'metadata' => $item->metadata,
            ]);

        } catch (\Exception $e) {
            Log::error('MySQLSearchAdapter::suggest error', [
                'query' => $partialQuery,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHealthy(): bool
    {
        try {
            // Verificar que la tabla existe y es accesible
            DB::select('SELECT 1 FROM search_index LIMIT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        try {
            $stats = SearchIndex::query()
                ->selectRaw('searchable_type, COUNT(*) as count, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count')
                ->groupBy('searchable_type')
                ->get();

            $total = SearchIndex::count();
            $active = SearchIndex::where('is_active', true)->count();

            return [
                'total_documents' => $total,
                'active_documents' => $active,
                'inactive_documents' => $total - $active,
                'by_type' => $stats->pluck('count', 'searchable_type')->toArray(),
                'active_by_type' => $stats->pluck('active_count', 'searchable_type')->toArray(),
                'last_indexed' => SearchIndex::max('updated_at'),
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'healthy' => false,
            ];
        }
    }
}
