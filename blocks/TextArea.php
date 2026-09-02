<?php
require_once __DIR__ . '/BlockInterface.php';

class HeroBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'title'    => ['type' => 'text',  'label' => 'Overskrift'],
            'address'  => ['type' => 'text',  'label' => 'Adresse'],
            'phone'    => ['type' => 'text',  'label' => 'Telefon'],
            'bg_image' => ['type' => 'image', 'label' => 'Baggrundsbillede'],
            'style'    => ['type' => 'style', 'label' => 'Styling'],
        ];
    }

    public static function render(array $data): string {
        $title   = htmlspecialchars($data['title']   ?? '');
        $address = htmlspecialchars($data['address'] ?? '');
        $phone   = htmlspecialchars($data['phone']   ?? '');
        $bg      = htmlspecialchars($data['bg_image'] ?? '');
        $style   = $data['style'] ?? ['color' => '#000000', 'size' => '16', 'family' => 'inherit'];
        $styleAttr = sprintf(
            'color:%s;font-size:%spx;font-family:%s;',
            htmlspecialchars($style['color']  ?? '#000000'),
            htmlspecialchars($style['size']   ?? '16'),
            htmlspecialchars($style['family'] ?? 'inherit')
        );

        return "
            <section class='hero' style=\"background-image:url('{$bg}')\">
                <div class='hero-box' style=\"{$styleAttr}\">
                    <h1>{$title}</h1>
                    <span>{$address}</span>
                    <span>{$phone}</span>
                </div>
            </section>
        ";
    }
}