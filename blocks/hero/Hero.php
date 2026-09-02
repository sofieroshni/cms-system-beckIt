<?php
declare(strict_types=1);

/**
 * Hero: fuldbredde-sektion med baggrundsbillede og en informationsboks.
 * Svarer til den øverste sektion i Figma-designet.
 */
final class HeroBlock extends AbstractBlock
{
    public static function type(): string
    {
        return 'hero';
    }

    public static function label(): string
    {
        return 'Hero';
    }

    public static function getSchema(): array
    {
        return [
            'title' => [
                'type'    => 'text',
                'label'   => 'Overskrift',
                'default' => 'Din overskrift her',
            ],
            'address' => [
                'type'    => 'text',
                'label'   => 'Adresse',
                'default' => 'Vejnavn 1, 1234 By',
            ],
            'phone' => [
                'type'    => 'text',
                'label'   => 'Telefon',
                'default' => '+45 00 00 00 00',
            ],
            'bg_image' => [
                'type'    => 'image',
                'label'   => 'Baggrundsbillede',
                'default' => 'assets/demo/hero-placeholder.jpg',
            ],
        ];
    }

    public static function getStyleSchema(): array
    {
        return [
            'title_size' => [
                'type'    => 'number',
                'label'   => 'Skriftstørrelse',
                'default' => 48,
                'min'     => 12,
                'max'     => 120,
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
                'default' => '#ffffff',
            ],
            'box_color' => [
                'type'    => 'color',
                'label'   => 'Boksfarve',
                'default' => '#1e3a8a',
            ],
        ];
    }

    public static function render(
        array $settings,
        array $styles,
        RenderContext $context
    ): string {
        return static::renderTemplate([
            'title'   => $settings['title']   ?? '',
            'address' => $settings['address'] ?? '',
            'phone'   => $settings['phone']   ?? '',

            // Konteksten oversætter den gemte sti til en URL, der virker
            // både i editoren og i den eksporterede side.
            'bgImage' => $context->asset((string) ($settings['bg_image'] ?? '')),

            'cssVars' => static::cssVariables($styles),
        ]);
    }
}