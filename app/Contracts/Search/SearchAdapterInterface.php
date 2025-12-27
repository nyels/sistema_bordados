<?php

namespace App\Contracts\Search;

use Illuminate\Support\Collection;

/**
 * SearchAdapterInterface
 * 
 * Contrato para adaptadores de búsqueda.
 * Permite cambiar de motor (MySQL, Meilisearch, Elasticsearch) sin modificar
 * la lógica de negocio.
 * 
 * @package App\Contracts\Search
 * @author Sistema de Diseños
 * @version 1.0
 */
interface SearchAdapterInterface
{
    /**
     * Ejecutar búsqueda con término normalizado.
     *
     * @param string $normalizedQuery Término ya normalizado
     * @param array $options Opciones adicionales (filters, limit, etc.)
     * @return Collection Colección de IDs de resultados con scores
     */
    public function search(string $normalizedQuery, array $options = []): Collection;

    /**
     * Indexar un modelo en el motor de búsqueda.
     *
     * @param int $modelId ID del modelo
     * @param string $modelType Tipo de modelo (Design, Category, etc.)
     * @param string $originalText Texto original sin normalizar
     * @param string $normalizedText Texto normalizado/tokenizado
     * @param array $metadata Datos adicionales para filtros
     * @return bool
     */
    public function index(
        int $modelId,
        string $modelType,
        string $originalText,
        string $normalizedText,
        array $metadata = []
    ): bool;

    /**
     * Eliminar un modelo del índice.
     *
     * @param int $modelId
     * @param string $modelType
     * @return bool
     */
    public function remove(int $modelId, string $modelType): bool;

    /**
     * Actualizar índice de un modelo existente.
     *
     * @param int $modelId
     * @param string $modelType
     * @param string $originalText
     * @param string $normalizedText
     * @param array $metadata
     * @return bool
     */
    public function update(
        int $modelId,
        string $modelType,
        string $originalText,
        string $normalizedText,
        array $metadata = []
    ): bool;

    /**
     * Limpiar todo el índice (útil para re-indexación).
     *
     * @param string|null $modelType Si se especifica, solo limpia ese tipo
     * @return bool
     */
    public function flush(?string $modelType = null): bool;

    /**
     * Obtener sugerencias de autocompletado.
     *
     * @param string $partialQuery Texto parcial del usuario
     * @param int $limit Número máximo de sugerencias
     * @return Collection
     */
    public function suggest(string $partialQuery, int $limit = 5): Collection;

    /**
     * Verificar si el adaptador está disponible y funcional.
     *
     * @return bool
     */
    public function isHealthy(): bool;

    /**
     * Obtener estadísticas del índice.
     *
     * @return array
     */
    public function getStats(): array;
}
