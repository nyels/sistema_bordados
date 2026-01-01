<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\Category;
use App\Services\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * SearchController
 * 
 * Controlador de búsqueda con soporte para:
 * - Búsqueda tradicional (form submit)
 * - Búsqueda AJAX en tiempo real
 * - Autocompletado
 * - Filtros avanzados
 * 
 * @package App\Http\Controllers
 */
class SearchController extends Controller
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Búsqueda principal de diseños (página completa).
     * Soporta tanto búsqueda tradicional como AJAX.
     */
    public function index(Request $request)
    {
        // Si es AJAX, delegar al método AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return $this->searchAjax($request);
        }

        $query = Design::with(['categories', 'primaryImage', 'variants']);

        // Aplicar búsqueda avanzada si hay término
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            
            // Obtener IDs que coinciden con búsqueda normalizada
            $matchingIds = $this->searchService->getMatchingIds(
                $searchTerm, 
                Design::class,
                ['limit' => 500] // Límite razonable
            );

            if (!empty($matchingIds)) {
                // Usar los IDs encontrados, mantener relevancia
                $idsString = implode(',', $matchingIds);
                $query->whereIn('id', $matchingIds)
                      ->orderByRaw("FIELD(id, {$idsString})");
            } else {
                // Fallback: búsqueda LIKE tradicional si el índice no tiene resultados
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }
        }

        // Filtro por categorías
        if ($request->filled('categories')) {
            $categoryIds = is_array($request->categories)
                ? $request->categories
                : [$request->categories];

            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filtro por atributos
        if ($request->filled('attributes')) {
            $attributeValueIds = is_array($request->attributes)
                ? $request->attributes
                : [$request->attributes];

            $query->whereHas('variants.attributeValues', function ($q) use ($attributeValueIds) {
                $q->whereIn('attribute_values.id', $attributeValueIds);
            });
        }

        // Filtro por rango de precio
        if ($request->filled('price_min')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '>=', $request->price_min);
            });
        }

        if ($request->filled('price_max')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '<=', $request->price_max);
            });
        }

        // Solo diseños activos
        $query->where('is_active', true);

        // Ordenamiento (si no hay búsqueda con relevancia)
        if (!$request->filled('q')) {
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['name', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }
        }

        // Resultados paginados
        $designs = $query->paginate(12)->withQueryString();

        // Datos para filtros
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.search.index', compact('designs', 'categories'));
    }

    /**
     * Búsqueda AJAX en tiempo real.
     * Retorna JSON con resultados parciales.
     */
    public function searchAjax(Request $request): JsonResponse
    {
        $term = $request->get('q', $request->get('term', ''));
        $categoryId = $request->get('category_id');
        $limit = min($request->get('limit', 20), 50); // Máximo 50
        $page = max($request->get('page', 1), 1);
        $offset = ($page - 1) * $limit;

        if (mb_strlen(trim($term)) < 1) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'message' => 'Ingresa al menos 1 caracter',
            ]);
        }

        try {
            // Opciones de búsqueda
            $options = [
                'model_type' => Design::class,
                'limit' => $limit,
                'offset' => $offset,
                'only_active' => true,
            ];

            if ($categoryId) {
                $options['category_ids'] = [$categoryId];
            }

            // Ejecutar búsqueda
            $results = $this->searchService->search($term, $options);

            // Obtener modelos completos con relaciones
            $designIds = $results->pluck('id')->toArray();
            
            $designs = Design::with(['primaryImage', 'categories', 'variants', 'exports'])
                ->whereIn('id', $designIds)
                ->get()
                ->keyBy('id');

            // Formatear respuesta manteniendo orden de relevancia
            $formattedResults = $results->map(function ($result) use ($designs, $term) {
                $design = $designs->get($result['id']);
                
                if (!$design) {
                    return null;
                }

                // Usar thumbnail_small para listados (carga rápida)
                $imageSrc = null;
                if ($design->primaryImage) {
                    $imageSrc = $design->primaryImage->thumbnail_small
                        ? asset('storage/' . $design->primaryImage->thumbnail_small)
                        : asset('storage/' . $design->primaryImage->file_path);
                }

                return [
                    'id' => $design->id,
                    'name' => $design->name,
                    'slug' => $design->slug,
                    'description' => $design->description,
                    'excerpt' => $result['excerpt'] ?? $this->truncate($design->description, 100),
                    'image' => $imageSrc,
                    'categories' => $design->categories->pluck('name')->toArray(),
                    'variants_count' => $design->variants->count(),
                    'exports_count' => $design->exports->count(),
                    'score' => $result['score'] ?? 1,
                    'url' => route('admin.designs.show', $design),
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
                'total' => $results->count(),
                'query' => $term,
                'page' => $page,
                'has_more' => $results->count() >= $limit,
            ]);

        } catch (\Exception $e) {
            Log::error('SearchController::searchAjax error', [
                'term' => $term,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Autocompletado rápido para campo de búsqueda.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->get('term', $request->get('q', ''));
        $limit = min($request->get('limit', 10), 20);

        if (mb_strlen(trim($term)) < 2) {
            return response()->json([]);
        }

        try {
            $suggestions = $this->searchService->autocomplete($term, $limit);

            // Agregar imagen thumbnail si está disponible
            $designIds = $suggestions->pluck('id')->toArray();
            $designs = Design::with('primaryImage')
                ->whereIn('id', $designIds)
                ->get()
                ->keyBy('id');

            $formatted = $suggestions->map(function ($item) use ($designs) {
                $design = $designs->get($item['id']);

                // Usar thumbnail_small para autocompletado (carga rápida)
                $imageSrc = null;
                if ($design && $design->primaryImage) {
                    $imageSrc = $design->primaryImage->thumbnail_small
                        ? asset('storage/' . $design->primaryImage->thumbnail_small)
                        : asset('storage/' . $design->primaryImage->file_path);
                }

                return [
                    'id' => $item['id'],
                    'value' => $item['title'], // Para jQuery UI Autocomplete
                    'label' => $item['title'], // Para jQuery UI Autocomplete
                    'name' => $item['title'],
                    'slug' => $item['metadata']['slug'] ?? null,
                    'image' => $imageSrc,
                ];
            });

            return response()->json($formatted);

        } catch (\Exception $e) {
            Log::error('SearchController::autocomplete error', [
                'term' => $term,
                'error' => $e->getMessage(),
            ]);

            // Fallback a búsqueda simple
            return $this->autocompleteFallback($term, $limit);
        }
    }

    /**
     * Fallback de autocompletado si el índice falla.
     */
    private function autocompleteFallback(string $term, int $limit): JsonResponse
    {
        $designs = Design::where('name', 'like', "%{$term}%")
            ->where('is_active', true)
            ->with('primaryImage')
            ->limit($limit)
            ->get();

        $formatted = $designs->map(function ($design) {
            // Usar thumbnail_small para fallback (carga rápida)
            $imageSrc = null;
            if ($design->primaryImage) {
                $imageSrc = $design->primaryImage->thumbnail_small
                    ? asset('storage/' . $design->primaryImage->thumbnail_small)
                    : asset('storage/' . $design->primaryImage->file_path);
            }

            return [
                'id' => $design->id,
                'value' => $design->name,
                'label' => $design->name,
                'name' => $design->name,
                'slug' => $design->slug,
                'image' => $imageSrc,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Endpoint para verificar estado del sistema de búsqueda.
     */
    public function healthCheck(): JsonResponse
    {
        $health = $this->searchService->healthCheck();
        $statusCode = $health['healthy'] ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Endpoint para estadísticas del índice (admin).
     */
    public function stats(): JsonResponse
    {
        $stats = $this->searchService->getStats();
        return response()->json($stats);
    }

    /**
     * Truncar texto para excerpts.
     */
    private function truncate(?string $text, int $length = 100): string
    {
        if (empty($text)) {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
