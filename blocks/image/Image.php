<?php
declare(strict_types=1);

/**
 * Image: et enkelt billede med beskrivelse og valgfri billedtekst.
 *
 * OM ALT-TEKST
 * Beskrivelsen er ikke pynt. Uden den er billedet usynligt for
 * skærmlæsere. Feltet er derfor med i skemaet med en forklarende etiket,
 * og templaten skriver altid alt-attributten ud — også når den er tom.
 *
 * En tom alt="" er den korrekte måde at fortælle en skærmlæser, at
 * billedet er ren dekoration. Havde vi udeladt attributten helt, ville
 * skærmlæseren i stedet læse filnavnet op, hvilket er værre end ingenting.
 */
final class ImageBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return 'Billede';
    }

    public static function getSchema(): array
    {
        return [
            'src' => [
                'type'    => 'image',
                'label'   => 'Billedfil',
                'default' => '',
            ],
            'alt' => [
                'type'    => 'text',
                'label'   => 'Beskrivelse (for skærmlæsere)',
                'default' => '',
            ],
            'caption' => [
                'type'    => 'text',
                'label'   => 'Billedtekst',
                'default' => '',
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
            'max_width' => [
                'type'    => 'number',
                'label'   => 'Maksimal bredde',
                'default' => 800,
                'min'     => 100,
                'max'     => 2000,
                'unit'    => 'px',
            ],
            'radius' => [
                'type'    => 'number',
                'label'   => 'Afrundede hjørner',
                'default' => 0,
                'min'     => 0,
                'max'     => 64,
                'unit'    => 'px',
            ],
            'background_color' => [
                'type'    => 'color',
                'label'   => 'Baggrundsfarve',
                'default' => '#ffffff',
            ],
        ];
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        $source = (string) ($settings['src'] ?? '');

        return static::renderTemplate([
            'src'     => $source !== '' ? $context->asset($source) : '',
            'alt'     => (string) ($settings['alt'] ?? ''),
            'caption' => (string) ($settings['caption'] ?? ''),
            'cssVars' => static::cssVariables($styles),
        ]);
    }
}