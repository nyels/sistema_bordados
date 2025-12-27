<?php

namespace App\Services\Search;

/**
 * SpanishStemmer
 * 
 * Implementación del algoritmo de stemming para español basado en 
 * el algoritmo Snowball (Porter Stemmer adaptado al español).
 * 
 * Stemming: Reduce palabras a su raíz común.
 * Ejemplo: "perros", "perro", "perrito" → "perr"
 * 
 * @package App\Services\Search
 * @author Sistema de Diseños
 * @version 1.0
 * @see https://snowballstem.org/algorithms/spanish/stemmer.html
 */
class SpanishStemmer
{
    /**
     * Vocales en español
     */
    private const VOWELS = ['a', 'e', 'i', 'o', 'u', 'á', 'é', 'í', 'ó', 'ú', 'ü'];

    /**
     * Sufijos que indican plural o flexiones comunes
     * Ordenados de mayor a menor longitud para greedy matching
     */
    private const STANDARD_SUFFIXES = [
        // Sufijos de 7+ caracteres
        'amientos', 'imientos', 'aciones', 'uciones',
        // Sufijos de 6 caracteres
        'amente', 'idades', 'adores', 'edores', 'idores',
        'ancias', 'encias', 'adores', 'mente',
        // Sufijos de 5 caracteres  
        'acion', 'ición', 'ables', 'ibles', 'istas',
        'anzas', 'encia', 'ancia', 'ador', 'edor', 'idor',
        'ante', 'ente', 'iente',
        // Sufijos de 4 caracteres
        'ando', 'endo', 'iendo', 'ados', 'idos', 'osas',
        'osos', 'icas', 'icos', 'idad', 'ivas', 'ivos',
        // Sufijos de 3 caracteres
        'aba', 'ada', 'ado', 'ían', 'ara', 'era', 'ase',
        'ese', 'ían', 'ido', 'ión', 'oso', 'osa', 'iva',
        'ivo', 'ble', 'dad', 'aje', 'ura',
        // Sufijos de 2 caracteres (plurales y género)
        'es', 'os', 'as', 'ar', 'er', 'ir', 'an', 'en',
        // Sufijos de 1 caracter (singular/género básico)
        's', 'a', 'o', 'e',
    ];

    /**
     * Sufijos verbales
     */
    private const VERB_SUFFIXES = [
        'aríamos', 'eríamos', 'iríamos', 'iéramos', 'iésemos',
        'áramos', 'aremos', 'aríais', 'eremos', 'eríais',
        'iremos', 'iríais', 'ásemos', 'asteis', 'isteis',
        'ábamos', 'arían', 'arías', 'aréis', 'erían',
        'erías', 'eréis', 'irían', 'irías', 'iréis',
        'ieran', 'iesen', 'ieron', 'iendo', 'ieras',
        'ieses', 'abais', 'arais', 'aseis', 'íamos',
        'amos', 'aron', 'aban', 'aran', 'asen', 'aste',
        'emos', 'aron', 'eran', 'esen', 'imos', 'iste',
        'ían', 'aré', 'erá', 'iré', 'aba', 'ada', 'ido',
        'ías', 'éis', 'ará', 'ido', 'ado', 'ais',
        'ar', 'er', 'ir', 'ía', 'ad', 'ed', 'id',
        'an', 'en', 'ás', 'és', 'ís', 'ió',
    ];

    /**
     * Excepciones - palabras que no deben ser stemmed
     */
    private const EXCEPTIONS = [
        'para' => 'para',
        'pero' => 'pero',
        'como' => 'como',
        'este' => 'este',
        'esta' => 'esta',
        'esto' => 'esto',
        'estos' => 'esto',
        'estas' => 'esta',
    ];

    /**
     * Cache de stems para evitar recálculos
     */
    private array $cache = [];

    /**
     * Tamaño máximo del cache
     */
    private int $maxCacheSize = 10000;

    /**
     * Aplicar stemming a una palabra.
     *
     * @param string $word Palabra a procesar
     * @return string Raíz de la palabra
     */
    public function stem(string $word): string
    {
        // Normalizar: minúsculas y sin espacios
        $word = mb_strtolower(trim($word));

        // Palabras muy cortas no se procesan
        if (mb_strlen($word) < 3) {
            return $word;
        }

        // Verificar cache
        if (isset($this->cache[$word])) {
            return $this->cache[$word];
        }

        // Verificar excepciones
        if (isset(self::EXCEPTIONS[$word])) {
            return $this->cacheAndReturn($word, self::EXCEPTIONS[$word]);
        }

        // Aplicar algoritmo de stemming
        $stem = $this->applySpanishStemming($word);

        return $this->cacheAndReturn($word, $stem);
    }

    /**
     * Aplicar stemming a múltiples palabras.
     *
     * @param array $words Array de palabras
     * @return array Array de stems
     */
    public function stemWords(array $words): array
    {
        return array_map([$this, 'stem'], $words);
    }

    /**
     * Aplicar stemming a un texto completo.
     *
     * @param string $text Texto completo
     * @return string Texto con palabras stemmed
     */
    public function stemText(string $text): string
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stemmed = $this->stemWords($words);
        return implode(' ', $stemmed);
    }

    /**
     * Algoritmo principal de stemming para español.
     *
     * @param string $word
     * @return string
     */
    private function applySpanishStemming(string $word): string
    {
        // Paso 0: Normalizar acentos para procesamiento interno
        $original = $word;
        
        // Paso 1: Remover sufijos estándar más largos primero
        $word = $this->removeStandardSuffixes($word);
        
        // Paso 2: Remover sufijos verbales si la palabra aún es larga
        if (mb_strlen($word) > 4) {
            $word = $this->removeVerbSuffixes($word);
        }

        // Paso 3: Remover sufijo residual si existe
        $word = $this->removeResidualSuffix($word);

        // Paso 4: Garantizar longitud mínima (no reducir demasiado)
        if (mb_strlen($word) < 2) {
            // Si quedó muy corto, usar los primeros caracteres del original
            $word = mb_substr($original, 0, max(2, (int)(mb_strlen($original) * 0.6)));
        }

        return $word;
    }

    /**
     * Remover sufijos estándar.
     *
     * @param string $word
     * @return string
     */
    private function removeStandardSuffixes(string $word): string
    {
        foreach (self::STANDARD_SUFFIXES as $suffix) {
            if ($this->endsWith($word, $suffix)) {
                $stem = mb_substr($word, 0, -mb_strlen($suffix));
                // Verificar que quede una raíz válida (mínimo 2 caracteres con vocal)
                if (mb_strlen($stem) >= 2 && $this->containsVowel($stem)) {
                    return $stem;
                }
            }
        }
        return $word;
    }

    /**
     * Remover sufijos verbales.
     *
     * @param string $word
     * @return string
     */
    private function removeVerbSuffixes(string $word): string
    {
        foreach (self::VERB_SUFFIXES as $suffix) {
            if ($this->endsWith($word, $suffix)) {
                $stem = mb_substr($word, 0, -mb_strlen($suffix));
                if (mb_strlen($stem) >= 2 && $this->containsVowel($stem)) {
                    return $stem;
                }
            }
        }
        return $word;
    }

    /**
     * Remover sufijo residual.
     *
     * @param string $word
     * @return string
     */
    private function removeResidualSuffix(string $word): string
    {
        $residuals = ['os', 'as', 'es', 'a', 'o', 'e'];
        
        foreach ($residuals as $suffix) {
            if ($this->endsWith($word, $suffix) && mb_strlen($word) > 3) {
                $stem = mb_substr($word, 0, -mb_strlen($suffix));
                if (mb_strlen($stem) >= 2 && $this->containsVowel($stem)) {
                    return $stem;
                }
            }
        }
        
        return $word;
    }

    /**
     * Verificar si la palabra termina con un sufijo.
     *
     * @param string $word
     * @param string $suffix
     * @return bool
     */
    private function endsWith(string $word, string $suffix): bool
    {
        $suffixLen = mb_strlen($suffix);
        if ($suffixLen > mb_strlen($word)) {
            return false;
        }
        return mb_substr($word, -$suffixLen) === $suffix;
    }

    /**
     * Verificar si la palabra contiene al menos una vocal.
     *
     * @param string $word
     * @return bool
     */
    private function containsVowel(string $word): bool
    {
        foreach (self::VOWELS as $vowel) {
            if (mb_strpos($word, $vowel) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Guardar en cache y retornar.
     *
     * @param string $original
     * @param string $stem
     * @return string
     */
    private function cacheAndReturn(string $original, string $stem): string
    {
        // Limpiar cache si es muy grande
        if (count($this->cache) >= $this->maxCacheSize) {
            $this->cache = array_slice($this->cache, (int)($this->maxCacheSize / 2), null, true);
        }

        $this->cache[$original] = $stem;
        return $stem;
    }

    /**
     * Limpiar cache de stems.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Obtener estadísticas del cache.
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'size' => count($this->cache),
            'max_size' => $this->maxCacheSize,
            'memory_bytes' => strlen(serialize($this->cache)),
        ];
    }
}
