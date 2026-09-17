<?php
declare(strict_types=1);

/**
 * TextSection: overskrift, brødtekst og en valgfri punktliste i en boks.
 *
 * Afløser den tidligere Welcome-blok. Layoutet er det samme; det er navnet
 * og tankegangen bag, der er ændret.
 *
 * HVORFOR OMDØBNINGEN
 * 'Welcome' var navngivet efter indholdet på én bestemt side. Så snart
 * næste side skulle bruge samme layout til noget andet, stod man med et
 * valg mellem at lave en kopi under et nyt navn eller at bruge en blok,
 * der hed noget forkert. Begge veje fører til femten blokke, der hver
 * bruges én gang.
 *
 * Reglen er: en blok navngives efter det MØNSTER, den tegner — ikke efter
 * den tekst, der tilfældigvis står i den første gang.
 *
 * Feltnavnene er bevidst uændrede fra Welcome, så eksisterende indhold i
 * databasen følger med uden at skulle omskrives. Kun etiketterne i
 * editoren er blevet mere generelle.
 */
final class TextSectionBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'textsection';
    }

    public static function label(): string
    {
        return 'Tekstsektion';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => 'Din overskrift',
            ],
            'intro' => [
                'type'    => 'textarea',
                'label'   => 'Brødtekst',
                'default' => 'Skriv en kort introduktion her.',
                'max'     => 2000,
            ],
            'list_title' => [
                'type'    => 'text',
                'label'   => 'Overskrift over listen',
                'default' => '',
            ],
            'items' => [
                'type'     => 'repeater',
                'label'    => 'Punkter',
                'max_rows' => 30,
                'fields'   => [
                    'text' => [
                        'type'    => 'text',
                        'label'   => 'Tekst',
                        'default' => '',
                    ],
                ],
                'default' => [],
            ],
            'footer_text' => [
                'type'    => 'textarea',
                'label'   => 'Afsluttende tekst',
                'default' => '',
                'max'     => 2000,
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        // Fællesfelterne arves; kun det særlige for denne blok beskrives.
        return self::sectionStyles([
            'background_color' => self::colorField('Baggrundsfarve', '#1e3a8a'),
            'text_color'       => self::colorField('Tekstfarve', '#ffffff'),
            'title_size'       => self::sizeField('Overskriftens størrelse', 36, 12, 96),
            'text_align'       => [
                'type'    => 'select',
                'label'   => 'Justering',
                'default' => 'center',
                'options' => ['left', 'center', 'right'],
            ],
        ]);
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $items = $settings['items'] ?? [];

        return static::renderTemplate([
            'title'      => (string) ($settings['title'] ?? ''),
            'intro'      => (string) ($settings['intro'] ?? ''),
            'listTitle'  => (string) ($settings['list_title'] ?? ''),
            'footerText' => (string) ($settings['footer_text'] ?? ''),
            'items'      => is_array($items) ? $items : [],
            'cssVars'    => static::cssVariables($styles),
        ]);
    }
}