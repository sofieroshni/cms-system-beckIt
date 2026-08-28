<?php
require_once __DIR__ . '/BlockInterface.php';

class ImageBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'src' => ['type' => 'image', 'label' => 'Billede'],
            'alt' => ['type' => 'text',  'label' => 'Alt-tekst'],
        ];
    }

    public static function render(array $data): string {
        $src = htmlspecialchars($data['src'] ?? '');
        $alt = htmlspecialchars($data['alt'] ?? '');
        return "<section class='image-block'><img src=\"{$src}\" alt=\"{$alt}\"></section>";
    }
}