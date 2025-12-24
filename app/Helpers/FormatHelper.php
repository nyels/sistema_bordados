<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * Formatear precio a moneda
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    public static function formatPrice(float $price, string $currency = 'MXN'): string
    {
        $symbols = [
            'USD' => '$',
            'MXN' => '$',
            'EUR' => '€',
        ];

        $symbol = $symbols[$currency] ?? '$';

        return $symbol . number_format($price, 2);
    }

    /**
     * Formatear tamaño de archivo
     *
     * @param int $bytes
     * @return string
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Formatear fecha de forma legible
     *
     * @param string|\DateTime $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, string $format = 'd/m/Y'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Formatear fecha relativa (hace X tiempo)
     *
     * @param string|\DateTime $date
     * @return string
     */
    public static function formatDateRelative($date): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) {
            return $diff->y . ' año' . ($diff->y > 1 ? 's' : '') . ' atrás';
        }
        if ($diff->m > 0) {
            return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '') . ' atrás';
        }
        if ($diff->d > 0) {
            return $diff->d . ' día' . ($diff->d > 1 ? 's' : '') . ' atrás';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
        }

        return 'Justo ahora';
    }

    /**
     * Truncar texto con puntos suspensivos
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    /**
     * Generar excerpt de texto HTML
     *
     * @param string $html
     * @param int $length
     * @return string
     */
    public static function excerpt(string $html, int $length = 150): string
    {
        $text = strip_tags($html);
        return self::truncate($text, $length);
    }
}
