<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * SearchIndex Model
 * 
 * Representa una entrada en el índice de búsqueda.
 * Cada registro es una copia denormalizada de un modelo searchable.
 * 
 * @package App\Models
 * @property int $id
 * @property string $searchable_type
 * @property int $searchable_id
 * @property string $original_title
 * @property string|null $original_content
 * @property string $normalized_title
 * @property string|null $normalized_content
 * @property string $stemmed_tokens
 * @property array|null $metadata
 * @property bool $is_active
 * @property float $boost
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SearchIndex extends Model
{
    protected $table = 'search_index';

    protected $fillable = [
        'searchable_type',
        'searchable_id',
        'original_title',
        'original_content',
        'normalized_title',
        'normalized_content',
        'stemmed_tokens',
        'metadata',
        'is_active',
        'boost',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'boost' => 'float',
        'searchable_id' => 'integer',
    ];

    /**
     * Relación polimórfica al modelo indexado.
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Solo entradas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filtrar por tipo de modelo.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('searchable_type', $type);
    }

    /**
     * Scope: Búsqueda FULLTEXT en tokens stemmed.
     * Este es el método principal de búsqueda.
     */
    public function scopeSearchStemmed(Builder $query, string $searchTerms): Builder
    {
        if (empty(trim($searchTerms))) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(stemmed_tokens) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerms]
        );
    }

    /**
     * Scope: Búsqueda FULLTEXT en texto normalizado.
     */
    public function scopeSearchNormalized(Builder $query, string $searchTerms): Builder
    {
        if (empty(trim($searchTerms))) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(normalized_title, normalized_content) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerms]
        );
    }

    /**
     * Scope: Búsqueda solo en título.
     */
    public function scopeSearchTitle(Builder $query, string $searchTerms): Builder
    {
        if (empty(trim($searchTerms))) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(normalized_title) AGAINST(? IN BOOLEAN MODE)',
            [$searchTerms]
        );
    }

    /**
     * Scope: Búsqueda con LIKE (fallback).
     */
    public function scopeSearchLike(Builder $query, string $term): Builder
    {
        $term = '%' . $term . '%';
        
        return $query->where(function ($q) use ($term) {
            $q->where('normalized_title', 'LIKE', $term)
              ->orWhere('normalized_content', 'LIKE', $term)
              ->orWhere('stemmed_tokens', 'LIKE', $term);
        });
    }

    /**
     * Scope: Ordenar por relevancia FULLTEXT.
     */
    public function scopeOrderByRelevance(Builder $query, string $searchTerms): Builder
    {
        if (empty(trim($searchTerms))) {
            return $query->orderByDesc('updated_at');
        }

        return $query->selectRaw(
            '*, MATCH(stemmed_tokens) AGAINST(? IN BOOLEAN MODE) AS relevance_score',
            [$searchTerms]
        )->orderByDesc('relevance_score')->orderByDesc('boost');
    }

    /**
     * Scope: Filtrar por metadata.
     */
    public function scopeWithMetadata(Builder $query, string $key, $value): Builder
    {
        return $query->whereRaw(
            'JSON_EXTRACT(metadata, ?) = ?',
            ['$.' . $key, json_encode($value)]
        );
    }

    /**
     * Scope: Filtrar por categorías en metadata.
     */
    public function scopeInCategories(Builder $query, array $categoryIds): Builder
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($categoryIds) {
            foreach ($categoryIds as $catId) {
                $q->orWhereRaw(
                    'JSON_CONTAINS(metadata, ?, "$.category_ids")',
                    [json_encode((int)$catId)]
                );
            }
        });
    }

    /**
     * Obtener el modelo original indexado.
     */
    public function getOriginalModel(): ?Model
    {
        $class = $this->searchable_type;
        
        if (!class_exists($class)) {
            return null;
        }

        return $class::find($this->searchable_id);
    }

    /**
     * Verificar si el índice está desactualizado.
     */
    public function isStale(): bool
    {
        $model = $this->getOriginalModel();
        
        if (!$model) {
            return true; // El modelo ya no existe
        }

        return $model->updated_at > $this->updated_at;
    }

    /**
     * Obtener extracto con highlight de términos.
     */
    public function getHighlightedExcerpt(string $query, int $length = 150): string
    {
        $content = $this->original_content ?? $this->original_title;
        
        if (empty($content)) {
            return '';
        }

        // Truncar si es muy largo
        if (mb_strlen($content) > $length) {
            $content = mb_substr($content, 0, $length) . '...';
        }

        // Destacar términos de búsqueda
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($terms as $term) {
            $content = preg_replace(
                '/(' . preg_quote($term, '/') . ')/iu',
                '<mark>$1</mark>',
                $content
            );
        }

        return $content;
    }

    /**
     * Crear o actualizar índice para un modelo.
     */
    public static function indexModel(
        Model $model,
        string $title,
        ?string $content,
        string $normalizedTitle,
        ?string $normalizedContent,
        string $stemmedTokens,
        array $metadata = [],
        float $boost = 1.0
    ): self {
        return self::updateOrCreate(
            [
                'searchable_type' => get_class($model),
                'searchable_id' => $model->getKey(),
            ],
            [
                'original_title' => $title,
                'original_content' => $content,
                'normalized_title' => $normalizedTitle,
                'normalized_content' => $normalizedContent,
                'stemmed_tokens' => $stemmedTokens,
                'metadata' => $metadata,
                'is_active' => true,
                'boost' => $boost,
            ]
        );
    }

    /**
     * Eliminar índice de un modelo.
     */
    public static function removeModel(Model $model): bool
    {
        return self::where('searchable_type', get_class($model))
            ->where('searchable_id', $model->getKey())
            ->delete() > 0;
    }

    /**
     * Desactivar índice de un modelo (soft).
     */
    public static function deactivateModel(Model $model): bool
    {
        return self::where('searchable_type', get_class($model))
            ->where('searchable_id', $model->getKey())
            ->update(['is_active' => false]) > 0;
    }
}
