<?php
declare(strict_types=1);

/**
 * TextArea: overskrift og brødtekst.
 *
 * Den enkleste blok i systemet og et godt udgangspunkt, hvis I skal bygge
 * en ny: den bruger kun almindelige felter og har ingen særlige tilfælde.
 */
final class TextAreaBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'textarea';
    }

    public static function label(): string
    {
        return 'Tekst';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => '',
            ],
            'body' => [
                'type'    => 'textarea',
                'label'   => 'Tekst',
                'default' => 'Skriv din tekst her.',
                'max'     => 5000,
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
            'title_size' => [
                'type'    => 'number',
                'label'   => 'Overskriftens størrelse',
                'default' => 28,
                'min'     => 12,
                'max'     => 72,
                'unit'    => 'px',
            ],
            'text_size' => [
                'type'    => 'number',
                'label'   => 'Tekstens størrelse',
                'default' => 16,
                'min'     => 10,
                'max'     => 32,
                'unit'    => 'px',
            ],
            'font_family' => [
                'type'    => 'select',
                'label'   => 'Skrifttype',
                'default' => 'Jost',
                'options' => FieldValidator::ALLOWED_FONTS,
            ],
            'text_color' => [
                'type'    => 'color',
                'label'   => 'Tekstfarve',
                'default' => '#1f2933',
            ],
            'background_color' => [
                'type'    => 'color',
                'label'   => 'Baggrundsfarve',
                'default' => '#ffffff',
            ],
            'text_align' => [
                'type'    => 'select',
                'label'   => 'Justering',
                'default' => 'left',
                'options' => ['left', 'center', 'right'],
            ],
        ];
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        return static::renderTemplate([
            'title'   => (string) ($settings['title'] ?? ''),
            'body'    => (string) ($settings['body'] ?? ''),
            'cssVars' => static::cssVariables($styles),
        ]);
    }
}