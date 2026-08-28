<?php
require_once __DIR__ . '/BlockInterface.php';

class TextAreaBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'content' => ['type' => 'richtext', 'label' => 'Tekst'],
        ];
    }

    public static function render(array $data): string {
        $content = htmlspecialchars($data['content'] ?? '');
        return "<section class='textarea-block'><p>{$content}</p></section>";
    }
}