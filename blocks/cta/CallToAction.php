<?php
declare(strict_types=1);

/**
 * CallToAction: kort tekst og én knap, centreret.
 *
 * Dækker "Tilmelding på hjemmesiden" i designet. Blokken har med vilje få
 * felter — en opfordring med tre knapper og fem afsnit er ikke længere en
 * opfordring.
 */
final class CallToActionBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'cta';
    }

    public static function label(): string
    {
        return 'Opfordring';
    }

    public static function getSchema(): array
    {
        return array_merge(
            [
                'title' => [
                    'type'    => 'text',
                    'label'   => 'Overskrift',
                    'default' => 'Tilmelding på hjemmesiden',
                ],
                'text' => [
                    'type'    => 'textarea',
                    'label'   => 'Uddybende tekst',
                    'default' => '',
                    'max'     => 500,
                ],
                'button_label' => [
                    'type'    => 'text',
                    'label'   => 'Knaptekst',
                    'default' => 'Tryk her',
                ],
            ],
            self::linkFields()
        );
    }

    public static function getStyleSchema(): array
    {
        return self::sectionStyles([
            'background_color' => self::colorField('Baggrundsfarve', '#ffffff'),
            'text_color'       => self::colorField('Tekstfarve', '#1f2933'),
            'button_color'     => self::colorField('Knappens farve', '#9db4d8'),
            'title_size'       => self::sizeField('Overskriftens størrelse', 18, 12, 64),
        ]);
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $label = trim((string) ($settings['button_label'] ?? ''));

        return static::renderTemplate([
            'title'       => (string) ($settings['title'] ?? ''),
            'text'        => (string) ($settings['text'] ?? ''),
            'buttonLabel' => $label,
            'buttonHref'  => $label !== '' ? static::linkHref($settings, $context) : '',
            'cssVars'     => static::cssVariables($styles),
        ]);
    }
}