<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Adapter
    |--------------------------------------------------------------------------
    |
    | El adaptador a utilizar para búsquedas. Opciones disponibles:
    | - mysql: Usa FULLTEXT de MySQL (default, sin dependencias externas)
    | - meilisearch: Usa Meilisearch (requiere servidor externo)
    | - elasticsearch: Usa Elasticsearch (requiere servidor externo)
    |
    */
    'adapter' => env('SEARCH_ADAPTER', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Indexing
    |--------------------------------------------------------------------------
    |
    | Si es true, los modelos se indexarán automáticamente cuando se
    | creen, actualicen o eliminen usando Observers.
    |
    */
    'auto_index' => env('SEARCH_AUTO_INDEX', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuración del caché de resultados de búsqueda.
    |
    */
    'cache' => [
        'enabled' => env('SEARCH_CACHE_ENABLED', true),
        'ttl' => env('SEARCH_CACHE_TTL', 300), // segundos
        'prefix' => 'search:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stemming
    |--------------------------------------------------------------------------
    |
    | Configuración del stemmer para normalización de texto.
    |
    */
    'stemming' => [
        'enabled' => env('SEARCH_STEMMING_ENABLED', true),
        'language' => 'es', // español
        'cache_size' => 10000, // máximo de stems en cache
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Settings
    |--------------------------------------------------------------------------
    |
    | Configuración por defecto para queries de búsqueda.
    |
    */
    'query' => [
        'min_length' => 2, // mínimo de caracteres para buscar
        'max_results' => 500, // máximo de resultados
        'default_limit' => 20, // resultados por página default
        'autocomplete_limit' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Boost Settings
    |--------------------------------------------------------------------------
    |
    | Factores de boost para ordenamiento por relevancia.
    |
    */
    'boost' => [
        'title_match' => 2.0, // coincidencia en título
        'exact_match' => 3.0, // coincidencia exacta
        'recent_days' => 30, // días para considerar "reciente"
        'recent_boost' => 0.1, // boost extra para items recientes
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Fulltext Settings
    |--------------------------------------------------------------------------
    |
    | Configuración específica para el adaptador MySQL.
    |
    */
    'mysql' => [
        'min_word_length' => 2, // ft_min_word_len de MySQL
        'boolean_mode' => true, // usar BOOLEAN MODE
        'natural_language_mode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Settings (Futuro)
    |--------------------------------------------------------------------------
    */
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY', ''),
        'index' => env('MEILISEARCH_INDEX', 'designs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    |
    | Lista de modelos que son indexables para búsqueda.
    |
    */
    'searchable_models' => [
        \App\Models\Design::class,
        // \App\Models\Category::class, // Agregar si se necesita
    ],
];
