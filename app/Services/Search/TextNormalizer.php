<?php

namespace App\Services\Search;

/**
 * TextNormalizer
 * 
 * Servicio de normalización de texto para búsqueda.
 * Responsabilidades:
 * - Remover acentos
 * - Normalizar mayúsculas
 * - Tokenizar texto
 * - Aplicar stemming
 * - Remover stopwords
 * 
 * @package App\Services\Search
 * @author Sistema de Diseños
 * @version 1.0
 */
class TextNormalizer
{
    private SpanishStemmer $stemmer;

    /**
     * Stopwords en español - palabras muy comunes que no aportan a la búsqueda
     */
    private const STOPWORDS_ES = [
        'de', 'la', 'que', 'el', 'en', 'y', 'a', 'los', 'del', 'se', 'las',
        'por', 'un', 'para', 'con', 'no', 'una', 'su', 'al', 'lo', 'como',
        'más', 'mas', 'pero', 'sus', 'le', 'ya', 'o', 'este', 'sí', 'si',
        'porque', 'esta', 'entre', 'cuando', 'muy', 'sin', 'sobre', 'también',
        'me', 'hasta', 'hay', 'donde', 'quien', 'desde', 'todo', 'nos',
        'durante', 'todos', 'uno', 'les', 'ni', 'contra', 'otros', 'ese',
        'eso', 'ante', 'ellos', 'e', 'esto', 'mí', 'antes', 'algunos',
        'qué', 'unos', 'yo', 'otro', 'otras', 'otra', 'él', 'tanto', 'esa',
        'estos', 'mucho', 'quienes', 'nada', 'muchos', 'cual', 'poco', 'ella',
        'estar', 'estas', 'algunas', 'algo', 'nosotros', 'mi', 'mis', 'tú',
        'tu', 'tus', 'ellas', 'nosotras', 'vosotros', 'vosotras', 'os',
        'mío', 'tuyo', 'suyo', 'nuestro', 'vuestro', 'esos', 'esas',
        'estoy', 'estás', 'está', 'estamos', 'estáis', 'están', 'como',
        'fue', 'era', 'han', 'ser', 'es', 'soy', 'eres', 'somos', 'son',
    ];

    /**
     * Mapa de caracteres acentuados a sin acento
     */
    private const ACCENT_MAP = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
    ];

    /**
     * Caracteres especiales a remover
     */
    private const SPECIAL_CHARS_PATTERN = '/[^\p{L}\p{N}\s]/u';

    public function __construct(?SpanishStemmer $stemmer = null)
    {
        $this->stemmer = $stemmer ?? new SpanishStemmer();
    }

    /**
     * Normalizar texto completamente para indexación.
     * Aplica todas las transformaciones.
     *
     * @param string $text Texto original
     * @param bool $applyStemming Si aplicar stemming
     * @param bool $removeStopwords Si remover stopwords
     * @return string Texto normalizado
     */
    public function normalize(
        string $text,
        bool $applyStemming = true,
        bool $removeStopwords = true
    ): string {
        // 1. Normalizar a minúsculas
        $text = mb_strtolower($text);

        // 2. Remover acentos
        $text = $this->removeAccents($text);

        // 3. Remover caracteres especiales
        $text = $this->removeSpecialChars($text);

        // 4. Tokenizar
        $tokens = $this->tokenize($text);

        // 5. Remover stopwords (opcional)
        if ($removeStopwords) {
            $tokens = $this->removeStopwords($tokens);
        }

        // 6. Aplicar stemming (opcional)
        if ($applyStemming) {
            $tokens = $this->stemmer->stemWords($tokens);
        }

        // 7. Remover duplicados manteniendo orden
        $tokens = array_values(array_unique($tokens));

        return implode(' ', $tokens);
    }

    /**
     * Normalizar query de búsqueda del usuario.
     * Más permisivo que la normalización de indexación.
     *
     * @param string $query
     * @param bool $applyStemming
     * @return string
     */
    public function normalizeQuery(string $query, bool $applyStemming = true): string
    {
        // Misma normalización pero sin remover stopwords
        // (el usuario puede buscar "el perro" intencionalmente)
        return $this->normalize($query, $applyStemming, false);
    }

    /**
     * Normalizar para autocompletado (sin stemming).
     *
     * @param string $query
     * @return string
     */
    public function normalizeForAutocomplete(string $query): string
    {
        $text = mb_strtolower(trim($query));
        $text = $this->removeAccents($text);
        $text = $this->removeSpecialChars($text);
        return $text;
    }

    /**
     * Remover acentos del texto.
     *
     * @param string $text
     * @return string
     */
    public function removeAccents(string $text): string
    {
        return strtr($text, self::ACCENT_MAP);
    }

    /**
     * Remover caracteres especiales.
     *
     * @param string $text
     * @return string
     */
    public function removeSpecialChars(string $text): string
    {
        // Remover caracteres que no son letras, números o espacios
        $text = preg_replace(self::SPECIAL_CHARS_PATTERN, ' ', $text);
        
        // Normalizar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Tokenizar texto en palabras.
     *
     * @param string $text
     * @return array
     */
    public function tokenize(string $text): array
    {
        // Dividir por espacios
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtrar tokens muy cortos (menos de 2 caracteres)
        return array_filter($tokens, fn($token) => mb_strlen($token) >= 2);
    }

    /**
     * Remover stopwords de un array de tokens.
     *
     * @param array $tokens
     * @return array
     */
    public function removeStopwords(array $tokens): array
    {
        return array_values(array_filter(
            $tokens,
            fn($token) => !in_array($token, self::STOPWORDS_ES, true)
        ));
    }

    /**
     * Generar variantes de búsqueda para un término.
     * Útil para "fuzzy matching" básico.
     *
     * @param string $term
     * @return array Variantes del término
     */
    public function generateVariants(string $term): array
    {
        $variants = [$term];
        $normalized = $this->removeAccents(mb_strtolower($term));
        
        if ($normalized !== $term) {
            $variants[] = $normalized;
        }

        // Agregar stem
        $stem = $this->stemmer->stem($normalized);
        if ($stem !== $normalized) {
            $variants[] = $stem;
        }

        // Variantes comunes de plural/singular
        if (str_ends_with($normalized, 's') && mb_strlen($normalized) > 3) {
            $variants[] = mb_substr($normalized, 0, -1); // Sin 's' final
        } else {
            $variants[] = $normalized . 's'; // Con 's'
            $variants[] = $normalized . 'es'; // Con 'es'
        }

        return array_values(array_unique($variants));
    }

    /**
     * Calcular similitud entre dos textos normalizados.
     * Usa coeficiente de Jaccard sobre tokens.
     *
     * @param string $text1
     * @param string $text2
     * @return float Valor entre 0 y 1
     */
    public function calculateSimilarity(string $text1, string $text2): float
    {
        $tokens1 = array_unique($this->tokenize($this->normalize($text1)));
        $tokens2 = array_unique($this->tokenize($this->normalize($text2)));

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Extraer palabras clave de un texto.
     * Retorna las palabras más significativas.
     *
     * @param string $text
     * @param int $limit
     * @return array
     */
    public function extractKeywords(string $text, int $limit = 5): array
    {
        $normalized = $this->normalize($text, true, true);
        $tokens = $this->tokenize($normalized);

        // Contar frecuencia
        $frequency = array_count_values($tokens);
        arsort($frequency);

        return array_slice(array_keys($frequency), 0, $limit);
    }

    /**
     * Preparar texto para FULLTEXT search de MySQL.
     * Agrega operadores booleanos.
     *
     * @param string $query
     * @param string $mode 'AND' | 'OR' | 'NATURAL'
     * @return string
     */
    public function prepareForFulltext(string $query, string $mode = 'AND'): string
    {
        $tokens = $this->tokenize($this->normalizeQuery($query, false));
        
        if (empty($tokens)) {
            return '';
        }

        switch (strtoupper($mode)) {
            case 'AND':
                // Todos los términos deben coincidir
                return implode(' ', array_map(fn($t) => '+' . $t . '*', $tokens));
            
            case 'OR':
                // Cualquier término puede coincidir
                return implode(' ', array_map(fn($t) => $t . '*', $tokens));
            
            case 'NATURAL':
            default:
                return implode(' ', $tokens);
        }
    }

    /**
     * Obtener el stemmer para uso directo.
     *
     * @return SpanishStemmer
     */
    public function getStemmer(): SpanishStemmer
    {
        return $this->stemmer;
    }

    /**
     * Verificar si un texto contiene una palabra (normalizada).
     *
     * @param string $text
     * @param string $word
     * @return bool
     */
    public function containsWord(string $text, string $word): bool
    {
        $normalizedText = $this->normalize($text);
        $normalizedWord = $this->normalize($word);
        
        $textTokens = $this->tokenize($normalizedText);
        $wordTokens = $this->tokenize($normalizedWord);

        foreach ($wordTokens as $searchToken) {
            $found = false;
            foreach ($textTokens as $textToken) {
                if ($textToken === $searchToken || str_starts_with($textToken, $searchToken)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }
}
