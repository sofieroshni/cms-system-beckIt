<?php
declare(strict_types=1);

/**
 * Footer: logo, links og en afsluttende linje.
 *
 * Modstykket til Navbar og bygget på samme måde, så begge kan blive til
 * delte blokke i næste fase uden at skulle laves om.
 */
final class FooterBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'footer';
    }

    public static function label(): string
    {
        return 'Footer';
    }

    public static function getSchema(): array
    {
        return [
            'logo' => [
                'type'    => 'image',
                'label'   => 'Logo',
                'default' => '',
            ],
            'logo_alt' => [
                'type'    => 'text',
                'label'   => 'Beskrivelse af logo',
                'default' => '',
            ],
            'links' => [
                'type'     => 'repeater',
                'label'    => 'Links',
                'max_rows' => 40,
                'fields'   => array_merge(
                    [
                        'label' => [
                            'type'    => 'text',
                            'label'   => 'Tekst',
                            'default' => '',
                        ],
                    ],
                    self::linkFields()
                ),
                'default' => [],
            ],
            'note' => [
                'type'    => 'text',
                'label'   => 'Afsluttende linje',
                'default' => '',
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return self::sectionStyles([
            'background_color' => self::colorField('Baggrundsfarve', '#1e3a8a'),
            'text_color'       => self::colorField('Tekstfarve', '#ffffff'),
            'link_size'        => self::sizeField('Skriftstørrelse', 13, 9, 32),
        ]);
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $links = [];

        foreach ((array) ($settings['links'] ?? []) as $link) {
            if (!is_array($link)) {
                continue;
            }

            $text = trim((string) ($link['label'] ?? ''));

            if ($text === '') {
                continue;
            }

            $links[] = [
                'label' => $text,
                'href'  => static::linkHref($link, $context),
            ];
        }

        $logo = (string) ($settings['logo'] ?? '');

        return static::renderTemplate([
            'logo'    => $logo !== '' ? $context->asset($logo) : '',
            'logoAlt' => (string) ($settings['logo_alt'] ?? ''),
            'links'   => $links,
            'note'    => (string) ($settings['note'] ?? ''),
            'cssVars' => static::cssVariables($styles),
        ]);
    }
}