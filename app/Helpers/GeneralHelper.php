<?php

namespace App\Helpers;

class GeneralHelper
{
    /**
     * Generar badge HTML para estado
     *
     * @param bool $isActive
     * @return string
     */
    public static function statusBadge(bool $isActive): string
    {
        if ($isActive) {
            return '<span class="badge bg-success">Activo</span>';
        }

        return '<span class="badge bg-danger">Inactivo</span>';
    }

    /**
     * Obtener clase CSS para estado
     *
     * @param bool $isActive
     * @return string
     */
    public static function statusClass(bool $isActive): string
    {
        return $isActive ? 'text-success' : 'text-danger';
    }

    /**
     * Generar opciones para select de estados
     *
     * @return array
     */
    public static function statusOptions(): array
    {
        return [
            1 => 'Activo',
            0 => 'Inactivo',
        ];
    }

    /**
     * Generar breadcrumbs
     *
     * @param array $items [['title' => 'Home', 'url' => '/'], ...]
     * @return string
     */
    public static function breadcrumbs(array $items): string
    {
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

        foreach ($items as $index => $item) {
            $isLast = $index === count($items) - 1;

            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($item['title']) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . e($item['url']) . '">' . e($item['title']) . '</a></li>';
            }
        }

        $html .= '</ol></nav>';

        return $html;
    }

    /**
     * Pluralizar palabra en español
     *
     * @param int $count
     * @param string $singular
     * @param string|null $plural
     * @return string
     */
    public static function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        if ($count === 1) {
            return $count . ' ' . $singular;
        }

        if ($plural === null) {
            // Regla simple: agregar 's' o 'es'
            $plural = (substr($singular, -1) === 's') ? $singular : $singular . 's';
        }

        return $count . ' ' . $plural;
    }
}
