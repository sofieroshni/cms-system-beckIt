<?php
declare(strict_types=1);

/**
 * Hjælpefunktioner til brug i blokkenes template.php-filer.
 *
 * De er bevidst korte. En template skal være læsbar som HTML, og
 * `<?= e($title) ?>` støjer mindre end det fulde funktionsnavn.
 */

if (!function_exists('e')) {
    /**
     * Escaper en værdi til HTML-tekst og HTML-attributter.
     *
     * ENT_QUOTES dækker både enkelt- og dobbeltanførselstegn, så
     * funktionen er sikker inde i attributter.
     *
     * VIGTIGT: Denne funktion er IKKE tilstrækkelig i en CSS-kontekst.
     * Værdier der ender i style-attributter, skal valideres af
     * FieldValidator i stedet — se dokumentationen i den klasse.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('eAttr')) {
    /**
     * Bygger en række HTML-attributter ud fra et array.
     * Tomme værdier udelades, så vi ikke skriver style="" på hver blok.
     *
     * @param array<string, string|null> $attributes
     */
    function eAttr(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = e($name) . '="' . e($value) . '"';
        }

        return $parts === [] ? '' : ' ' . implode(' ', $parts);
    }
}