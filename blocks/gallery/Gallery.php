<?php
declare(strict_types=1);

/**
 * Gallery: et gitter af billeder.
 *
 * Blokken er det første sted, hvor et repeater-felt indeholder billeder.
 * Det kræver ingen ny kode nogen steder — editoren tegner allerede
 * billedfelter i rækker, og SiteExporter går skemaet igennem og finder
 * billeder både i almindelige felter og i repeatere.
 *
 * Det er dét, den skemadrevne tilgang skulle give: en ny blok med en ny
 * kombination af felter virker overalt uden ændringer i core.
 */
final class GalleryBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'gallery';
    }

    public static function label(): string
    {
        return 'Galleri';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => '',
            ],
            'images' => [
                'type'     => 'repeater',
                'label'    => 'Billeder',
                'max_rows' => 40,
                'fields'   => [
                    'src' => [
                        'type'    => 'image',
                        'label'   => 'Billedfil',
                        'default' => '',
                    ],
                    'alt' => [
                        'type'    => 'text',
                        'label'   => 'Beskrivelse',
                        'default' => '',
                    ],
                    'caption' => [
                        'type'    => 'text',
                        'label'   => 'Billedtekst',
                        'default' => '',
                    ],
                ],
                'default' => [],
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
            // Kolonnetallet har ingen enhed — det er et rent antal, ikke
            // en maalt stoerrelse.
            'columns'          => self::sizeField('Antal kolonner', 3, 1, 6, ''),
            'gap'              => self::sizeField('Afstand mellem billeder', 16, 0, 64),
            'radius'           => self::sizeField('Afrundede hjørner', 4, 0, 64),
            'background_color' => self::colorField('Baggrundsfarve', '#ffffff'),
        ];
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $images = [];

        foreach ((array) ($settings['images'] ?? []) as $image) {
            if (!is_array($image)) {
                continue;
            }

            $source = trim((string) ($image['src'] ?? ''));

            // En række uden billedfil ville blive til et tomt hul i
            // gitteret. Den springes over frem for at blive tegnet.
            if ($source === '') {
                continue;
            }

            $images[] = [
                'src'     => $context->asset($source),
                'alt'     => (string) ($image['alt'] ?? ''),
                'caption' => (string) ($image['caption'] ?? ''),
            ];
        }

        return static::renderTemplate([
            'title'   => (string) ($settings['title'] ?? ''),
            'images'  => $images,
            'cssVars' => static::cssVariables($styles),
        ]);
    }
}