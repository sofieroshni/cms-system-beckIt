<?php
declare(strict_types=1);

/**
 * Navbar: logo og navigationslinks.
 *
 * Blokken demonstrerer felttypen 'page'. Et link peger på en side ved dens
 * id, ikke ved en adresse skrevet i hånden. Fordelene er konkrete:
 * brugeren kan ikke stave forkert, linket overlever at målsiden får en ny
 * slug, og RenderContext kan regne den rigtige relative sti ud, uanset
 * hvor dybt i mappestrukturen den side, linket står på, ender.
 *
 * Feltet 'url' er til eksterne adresser og bruges kun, når der ikke er
 * valgt en side. Rækkefølgen — side først, ellers url — er bevidst: den
 * interne henvisning er den robuste, så den vinder.
 */
final class NavbarBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'navbar';
    }

    public static function label(): string
    {
        return 'Navbar';
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
                'label'    => 'Menupunkter',
                'max_rows' => 30,
                'fields'   => [
                    'label' => [
                        'type'    => 'text',
                        'label'   => 'Tekst',
                        'default' => '',
                    ],
                    'page' => [
                        'type'    => 'page',
                        'label'   => 'Side',
                        'default' => 0,
                    ],
                    'url' => [
                        'type'    => 'url',
                        'label'   => 'Ekstern adresse',
                        'default' => '',
                    ],
                ],
                'default' => [
                    ['label' => 'Forside', 'page' => 0, 'url' => '#'],
                    ['label' => 'Kontakt', 'page' => 0, 'url' => '#'],
                ],
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
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
            'link_size' => [
                'type'    => 'number',
                'label'   => 'Skriftstørrelse',
                'default' => 16,
                'min'     => 10,
                'max'     => 48,
                'unit'    => 'px',
            ],
            'font_family' => [
                'type'    => 'select',
                'label'   => 'Skrifttype',
                'default' => 'Jost',
                'options' => FieldValidator::ALLOWED_FONTS,
            ],
        ];
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

            // Et menupunkt uden tekst ville være en usynlig klikflade.
            if ($text === '') {
                continue;
            }

            $pageId = (int) ($link['page'] ?? 0);

            // Adressen udregnes HER, ikke i templaten. Templaten skal kun
            // vise; den skal ikke vide noget om sitets struktur.
            $href = $pageId > 0
                ? $context->pageUrl($pageId)
                : (string) ($link['url'] ?? '#');

            $links[] = [
                'label' => $text,
                'href'  => $href !== '' ? $href : '#',
            ];
        }

        $logo = (string) ($settings['logo'] ?? '');

        return static::renderTemplate([
            'logo'     => $logo !== '' ? $context->asset($logo) : '',
            'logoAlt'  => (string) ($settings['logo_alt'] ?? ''),
            'links'    => $links,
            'cssVars'  => static::cssVariables($styles),
        ]);
    }
}