<?php
declare(strict_types=1);

/**
 * Fælles grundlag for alle blokke.
 *
 * Håndterer det, enhver blok ellers ville skulle gentage: standardværdier,
 * indlæsning af template.php, og oversættelsen fra stylingværdier til
 * CSS-variabler.
 *
 * En konkret blok arver herfra og behøver kun beskrive sine egne felter.
 */
abstract class AbstractBlock implements BlockInterface
{
    public static function getStyleSchema(): array
    {
        return [];
    }

    /**
     * Standardværdier for en ny blok, udledt af skemaet.
     *
     * Retter en fejl i den gamle add-block.php, som satte alle felter til
     * tom streng. Resultatet var, at en netop tilføjet blok var usynlig i
     * editoren, og brugeren ikke kunne se hvad der skulle udfyldes.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return self::defaultsFrom(static::getSchema());
    }

    /** @return array<string, mixed> */
    public static function defaultStyles(): array
    {
        return self::defaultsFrom(static::getStyleSchema());
    }

    /**
     * @param array<string, array<string, mixed>> $schema
     * @return array<string, mixed>
     */
    private static function defaultsFrom(array $schema): array
    {
        $defaults = [];

        foreach ($schema as $name => $field) {
            $defaults[$name] = $field['default'] ?? '';
        }

        return $defaults;
    }

    /**
     * Renderer blokkens template.php.
     *
     * Templaten ligger altid ved siden af blok-klassen, så en blok er én
     * selvstændig mappe: klasse, template og CSS samlet.
     *
     * @param array<string, mixed> $variables Bliver til variabler i templaten.
     */
    protected static function renderTemplate(array $variables): string
    {
        $directory = dirname((new ReflectionClass(static::class))->getFileName());
        $template  = $directory . '/template.php';

        if (!is_file($template)) {
            // Én manglende template må ikke vælte hele siden.
            error_log('Manglende template: ' . $template);
            return '';
        }

        // extract() gør $variables['title'] tilgængelig som $title i
        // templaten. EXTR_SKIP forhindrer, at et feltnavn kan overskrive
        // $template eller $directory og dermed pege på en anden fil.
        extract($variables, EXTR_SKIP);

        ob_start();
        include $template;

        return (string) ob_get_clean();
    }

    /**
     * Oversætter stylingværdier til CSS-variabler på blokkens wrapper.
     *
     * Resultat: style="--title-size:48px;--title-color:#c1121f"
     *
     * Blokkens CSS bruger derefter var(--title-size). Brugerens valg bliver
     * altså aldrig til vilkårlig CSS, kun til værdier i variabler, vi selv
     * har defineret. Kombineret med FieldValidator — der garanterer, at et
     * tal er et tal og en farve er hex — er der ingen vej til CSS-injection.
     *
     * @param array<string, mixed> $styles Validerede stylingværdier.
     */
    protected static function cssVariables(array $styles): string
    {
        $schema      = static::getStyleSchema();
        $declarations = [];

        foreach ($styles as $name => $value) {
            // Kun felter, skemaet kender. Ukendte nøgler ignoreres.
            if (!isset($schema[$name]) || $value === '' || $value === null) {
                continue;
            }

            $variable = '--' . str_replace('_', '-', $name);
            $unit     = $schema[$name]['unit'] ?? '';

            $declarations[] = $variable . ':' . $value . $unit;
        }

        return implode(';', $declarations);
    }
}