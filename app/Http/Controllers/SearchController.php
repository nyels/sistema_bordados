<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\Category;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Búsqueda avanzada de diseños
     */
    public function index(Request $request)
    {
        $query = Design::with(['categories', 'primaryImage', 'variants']);

        // Búsqueda por texto
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Filtro por categorías (múltiples)
        if ($request->filled('categories')) {
            $categoryIds = is_array($request->categories)
                ? $request->categories
                : [$request->categories];

            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filtro por atributos (color, tamaño, etc.)
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

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['name', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
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
     * Autocompletado para búsqueda
     */
    public function autocomplete(Request $request)
    {
        $term = $request->get('term', '');

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $designs = Design::where('name', 'like', "%{$term}%")
            ->where('is_active', true)
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        return response()->json($designs);
    }
}
