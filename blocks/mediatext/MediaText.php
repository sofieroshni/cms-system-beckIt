<?php
declare(strict_types=1);

/**
 * MediaText: billede i den ene side, tekst i den anden.
 *
 * Dækker citat-sektionen i designet, hvor gruppebilledet står ved siden af
 * et citat på blå baggrund. Siden billedet sidder på, er et stylingvalg —
 * så den samme blok kan bruges skiftevis ned gennem en side uden at der
 * skal to varianter til.
 */
final class MediaTextBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'mediatext';
    }

    public static function label(): string
    {
        return 'Billede og tekst';
    }

    public static function getSchema(): array
    {
        return [
            'image' => [
                'type'    => 'image',
                'label'   => 'Billede',
                'default' => '',
            ],
            'image_alt' => [
                'type'    => 'text',
                'label'   => 'Beskrivelse af billedet',
                'default' => '',
            ],
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift eller citat',
                'default' => '',
            ],
            'body' => [
                'type'    => 'textarea',
                'label'   => 'Tekst',
                'default' => '',
                'max'     => 2000,
            ],
            'caption' => [
                'type'    => 'text',
                'label'   => 'Kilde eller underskrift',
                'default' => '',
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return self::sectionStyles([
            'background_color' => self::colorField('Baggrund bag teksten', '#1e3a8a'),
            'text_color'       => self::colorField('Tekstfarve', '#ffffff'),
            'title_size'       => self::sizeField('Overskriftens størrelse', 22, 12, 64),
            'image_side'       => [
                'type'    => 'select',
                'label'   => 'Billedets placering',
                'default' => 'left',
                'options' => ['left', 'right'],
            ],
        ]);
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $image = (string) ($settings['image'] ?? '');

        return static::renderTemplate([
            'image'    => $image !== '' ? $context->asset($image) : '',
            'imageAlt' => (string) ($settings['image_alt'] ?? ''),
            'title'    => (string) ($settings['title'] ?? ''),
            'body'     => (string) ($settings['body'] ?? ''),
            'caption'  => (string) ($settings['caption'] ?? ''),

            // Placeringen styres af en klasse frem for af CSS-variablen
            // alene, fordi den skal kunne vende grid-raekkefoelgen om —
            // og det kan ikke goeres med en variabel i order-egenskaben
            // paa en maade, der ogsaa holder paa mobil.
            'sideClass' => ($styles['image_side'] ?? 'left') === 'right'
                ? 'mediatext--image-right'
                : 'mediatext--image-left',

            'cssVars' => static::cssVariables($styles),
        ]);
    }
}