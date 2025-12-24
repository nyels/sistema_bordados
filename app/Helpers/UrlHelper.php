<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class UrlHelper
{
    /**
     * Obtener URL completa de una imagen
     *
     * @param string|null $path
     * @param string $default
     * @return string
     */
    public static function imageUrl(?string $path, string $default = 'images/placeholder.png'): string
    {
        if (!$path) {
            return asset($default);
        }

        // Si la ruta ya es una URL completa, retornarla
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Si el archivo existe en storage público
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }

        // Retornar placeholder si no existe
        return asset($default);
    }

    /**
     * Obtener URL de thumbnail
     *
     * @param string|null $path
     * @param string $size (small, medium, large)
     * @return string
     */
    public static function thumbnailUrl(?string $path, string $size = 'medium'): string
    {
        if (!$path) {
            return asset('images/placeholder.png');
        }

        // Construir ruta de thumbnail
        $pathInfo = pathinfo($path);
        $thumbnailPath = "designs/thumbnails/{$size}/" . $pathInfo['basename'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }

        // Si no existe thumbnail, retornar imagen original
        return self::imageUrl($path);
    }

    /**
     * Verificar si la ruta actual coincide con la ruta dada
     *
     * @param string $routeName
     * @param mixed $parameters
     * @return bool
     */
    public static function isActiveRoute(string $routeName, $parameters = null): bool
    {
        if ($parameters) {
            return request()->routeIs($routeName) && request()->route()->parameters() == $parameters;
        }

        return request()->routeIs($routeName);
    }

    /**
     * Generar clase CSS para ruta activa
     *
     * @param string $routeName
     * @param string $activeClass
     * @param string $inactiveClass
     * @return string
     */
    public static function activeClass(
        string $routeName,
        string $activeClass = 'active',
        string $inactiveClass = ''
    ): string {
        return self::isActiveRoute($routeName) ? $activeClass : $inactiveClass;
    }
}
