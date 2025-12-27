<?php

namespace App\Providers;

use App\Contracts\Search\SearchAdapterInterface;
use App\Models\Design;
use App\Observers\DesignSearchObserver;
use App\Services\Search\Adapters\MySQLSearchAdapter;
use App\Services\Search\SearchService;
use App\Services\Search\SpanishStemmer;
use App\Services\Search\TextNormalizer;
use Illuminate\Support\ServiceProvider;

/**
 * SearchServiceProvider
 * 
 * Registra los servicios de búsqueda en el contenedor de Laravel.
 * Configura bindings, singletons y observers.
 * 
 * @package App\Providers
 */
class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar SpanishStemmer como singleton (tiene cache interno)
        $this->app->singleton(SpanishStemmer::class, function ($app) {
            return new SpanishStemmer();
        });

        // Registrar TextNormalizer como singleton
        $this->app->singleton(TextNormalizer::class, function ($app) {
            return new TextNormalizer(
                $app->make(SpanishStemmer::class)
            );
        });

        // Registrar el adaptador de búsqueda
        // Esto permite cambiar fácilmente a otro adaptador (Meilisearch, Elastic, etc.)
        $this->app->singleton(SearchAdapterInterface::class, function ($app) {
            // Aquí se puede leer de config para elegir adaptador
            $adapter = config('search.adapter', 'mysql');

            return match ($adapter) {
                'mysql' => new MySQLSearchAdapter(),
                // 'meilisearch' => new MeilisearchAdapter(), // Futuro
                // 'elasticsearch' => new ElasticsearchAdapter(), // Futuro
                default => new MySQLSearchAdapter(),
            };
        });

        // Registrar SearchService como singleton
        $this->app->singleton(SearchService::class, function ($app) {
            return new SearchService(
                $app->make(TextNormalizer::class),
                $app->make(SearchAdapterInterface::class)
            );
        });

        // Alias para conveniencia
        $this->app->alias(SearchService::class, 'search');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar observer para auto-indexación de diseños
        // Solo si el sistema de búsqueda está habilitado
        if (config('search.auto_index', true)) {
            Design::observe(DesignSearchObserver::class);
        }

        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../../config/search.php' => config_path('search.php'),
        ], 'search-config');

        // Publicar migraciones
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'search-migrations');
    }
}
