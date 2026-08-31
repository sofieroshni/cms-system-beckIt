<?php
require_once __DIR__ . '/BlockInterface.php';

class GalleryBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'images' => ['type' => 'image_list', 'label' => 'Billeder'],
        ];
    }

    public static function render(array $data): string {
        $images = $data['images'] ?? [];
        if (!is_array($images)) {
            $images = [];
        }

        $html = "<section class='gallery-block'>";
        foreach ($images as $img) {
            $safe = htmlspecialchars($img);
            $html .= "<img src=\"{$safe}\" alt=''>";
        }
        $html .= "</section>";
        return $html;
    }
}