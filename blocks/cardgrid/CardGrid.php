<?php
declare(strict_types=1);

/**
 * CardGrid: en overskrift og et gitter af kort med tekst og knap.
 *
 * Dækker to forskellige sektioner i designet — rækken under
 * "Begynderkursus" og info-kortene længere nede. At det er den samme blok
 * begge steder er pointen: blokke navngives efter mønsteret, ikke efter
 * indholdet.
 */
final class CardGridBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'cardgrid';
    }

    public static function label(): string
    {
        return 'Kortgitter';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => '',
            ],
            'cards' => [
                'type'     => 'repeater',
                'label'    => 'Kort',
                'max_rows' => 12,
                'fields'   => array_merge(
                    [
                        'text' => [
                            'type'    => 'textarea',
                            'label'   => 'Tekst',
                            'default' => '',
                            'max'     => 600,
                        ],
                        'button_label' => [
                            'type'    => 'text',
                            'label'   => 'Knaptekst',
                            'default' => '',
                        ],
                    ],
                    // Knappen får samme side/adresse-felter som alle andre
                    // links i systemet.
                    self::linkFields()
                ),
                'default' => [
                    ['text' => 'Skriv teksten til det første kort her.', 'button_label' => 'Tryk her'],
                    ['text' => 'Skriv teksten til det andet kort her.', 'button_label' => 'Tryk her'],
                    ['text' => 'Skriv teksten til det tredje kort her.', 'button_label' => 'Tryk her'],
                ],
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return self::sectionStyles([
            'background_color' => self::colorField('Baggrundsfarve', '#7a7a7a'),
            'text_color'       => self::colorField('Overskriftens farve', '#ffffff'),
            'card_color'       => self::colorField('Kortets baggrund', '#ffffff'),
            'columns'          => self::sizeField('Antal kolonner', 3, 1, 6, ''),
            'title_size'       => self::sizeField('Overskriftens størrelse', 24, 12, 72),
        ]);
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $cards = [];

        foreach ((array) ($settings['cards'] ?? []) as $card) {
            if (!is_array($card)) {
                continue;
            }

            $text  = trim((string) ($card['text'] ?? ''));
            $label = trim((string) ($card['button_label'] ?? ''));

            // Et kort uden hverken tekst eller knap ville være et tomt hul
            // i gitteret.
            if ($text === '' && $label === '') {
                continue;
            }

            $cards[] = [
                'text'        => $text,
                'buttonLabel' => $label,
                'buttonHref'  => $label !== '' ? static::linkHref($card, $context) : '',
            ];
        }

        return static::renderTemplate([
            'title'   => (string) ($settings['title'] ?? ''),
            'cards'   => $cards,
            'cssVars' => static::cssVariables($styles),
        ]);
    }
}