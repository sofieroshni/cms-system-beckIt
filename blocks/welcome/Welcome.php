<?php
declare(strict_types=1);

/**
 * Welcome: velkomstsektion med overskrift, brødtekst og en punktliste.
 * Svarer til "Velkommen til Kolding Bridge Center" i Figma-designet.
 *
 * Blokken demonstrerer repeater-feltet: punktlisten er et vilkårligt antal
 * rækker gemt i blokkens egen JSON. Det er dét, der gør, at vi slipper for
 * ægte indlejrede blokke med parent_id — og dermed for rekursiv rendering,
 * rekursiv validering og forældreløse rækker ved sletning.
 */
final class WelcomeBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'welcome';
    }

    public static function label(): string
    {
        return 'Velkomst';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => 'Velkommen',
            ],
            'intro' => [
                'type'    => 'textarea',
                'label'   => 'Introtekst',
                'default' => 'Skriv en kort introduktion her.',
                'max'     => 1000,
            ],
            'list_title' => [
                'type'    => 'text',
                'label'   => 'Overskrift over listen',
                'default' => 'Vi tilbyder:',
            ],
            'items' => [
                'type'     => 'repeater',
                'label'    => 'Punkter',
                'max_rows' => 20,
                'fields'   => [
                    'text' => [
                        'type'    => 'text',
                        'label'   => 'Tekst',
                        'default' => '',
                    ],
                ],
                'default' => [
                    ['text' => 'Første punkt'],
                    ['text' => 'Andet punkt'],
                    ['text' => 'Tredje punkt'],
                ],
            ],
            'footer_text' => [
                'type'    => 'textarea',
                'label'   => 'Afsluttende tekst',
                'default' => '',
                'max'     => 1000,
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
            'title_size' => [
                'type'    => 'number',
                'label'   => 'Skriftstørrelse på overskrift',
                'default' => 36,
                'min'     => 12,
                'max'     => 96,
                'unit'    => 'px',
            ],
            'font_family' => [
                'type'    => 'select',
                'label'   => 'Skrifttype',
                'default' => 'Jost',
                'options' => FieldValidator::ALLOWED_FONTS,
            ],
            'background_color' => [
                'type'    => 'color',
                'label'   => 'Baggrundsfarve',
                'default' => '#1e3a8a',
            ],
            'text_color' => [
                'type'    => 'color',
                'label'   => 'Tekstfarve',
                'default' => '#ffffff',
            ],
        ];
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $items = $settings['items'] ?? [];

        return static::renderTemplate([
            'title'      => $settings['title']       ?? '',
            'intro'      => $settings['intro']       ?? '',
            'listTitle'  => $settings['list_title']  ?? '',
            'footerText' => $settings['footer_text'] ?? '',
            'items'      => is_array($items) ? $items : [],
            'cssVars'    => static::cssVariables($styles),
        ]);
    }
}