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

    /* -----------------------------------------------------------------
       Feltfabrikker
       -----------------------------------------------------------------
       De samme stylingfelter gik igen i hver eneste blok: baggrundsfarve,
       tekstfarve, skrifttype, skriftstørrelse. Fem kopier af den samme
       definition betyder fem steder at rette, hvis fx listen over tilladte
       skrifttyper skal udvides.

       Metoderne her laver ét felt hver. En blok beskriver dermed kun det,
       der faktisk er særligt for den.
       ----------------------------------------------------------------- */

    /**
     * De stylingfelter, næsten enhver sektion har brug for.
     *
     * @param array<string, array<string, mixed>> $extra Blokkens egne felter.
     * @return array<string, array<string, mixed>>
     */
    protected static function sectionStyles(array $extra = []): array
    {
        // $extra lægges sidst, så en blok kan overskrive et fællesfelt —
        // fx give baggrundsfarven en anden standardværdi.
        return array_merge(
            [
                'background_color' => self::colorField('Baggrundsfarve', '#ffffff'),
                'text_color'       => self::colorField('Tekstfarve', '#1f2933'),
                'font_family'      => self::fontField(),
            ],
            $extra
        );
    }

    /** @return array<string, mixed> */
    protected static function colorField(string $label, string $default): array
    {
        return [
            'type'    => 'color',
            'label'   => $label,
            'default' => $default,
        ];
    }

    /** @return array<string, mixed> */
    protected static function fontField(string $default = 'Jost'): array
    {
        return [
            'type'    => 'select',
            'label'   => 'Skrifttype',
            'default' => $default,
            'options' => FieldValidator::ALLOWED_FONTS,
        ];
    }

    /**
     * Et talfelt med enhed. Bruges til skriftstørrelser, afstande og
     * hjørneradier.
     *
     * @return array<string, mixed>
     */
    protected static function sizeField(
        string $label,
        int $default,
        int $min = 10,
        int $max = 120,
        string $unit = 'px'
    ): array {
        return [
            'type'    => 'number',
            'label'   => $label,
            'default' => $default,
            'min'     => $min,
            'max'     => $max,
            'unit'    => $unit,
        ];
    }

    /**
     * Udregner adressen på et link, der enten peger på en side i systemet
     * eller på en ekstern adresse.
     *
     * Mønsteret gik igen i navbar, kort, knapper og footer. Reglen er den
     * samme hvert sted: en valgt side vinder over en skrevet adresse,
     * fordi den interne henvisning er den robuste — den overlever, at
     * målsiden får en ny slug eller flyttes ned under en forælder.
     *
     * @param array<string, mixed> $source Rækken eller feltsættet med
     *                                     nøglerne page og url.
     */
    protected static function linkHref(
        array $source,
        RenderContext $context,
        string $pageKey = 'page',
        string $urlKey = 'url'
    ): string {
        $pageId = (int) ($source[$pageKey] ?? 0);

        if ($pageId > 0) {
            return $context->pageUrl($pageId);
        }

        $url = trim((string) ($source[$urlKey] ?? ''));

        // '#' frem for tom streng: et href="" peger på den aktuelle side
        // og ville få browseren til at genindlæse ved klik.
        return $url !== '' ? $url : '#';
    }

    /**
     * Felterne til et link. Bruges i repeatere og som knap-felter.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function linkFields(string $prefix = ''): array
    {
        return [
            $prefix . 'page' => [
                'type'    => 'page',
                'label'   => 'Side',
                'default' => 0,
            ],
            $prefix . 'url' => [
                'type'    => 'url',
                'label'   => 'Ekstern adresse',
                'default' => '',
            ],
        ];
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