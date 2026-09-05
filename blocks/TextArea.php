<?php
require_once __DIR__ . '/BlockInterface.php';

class TextAreaBlock implements BlockInterface {
    public static function getSchema(): array {
        return [
            'contentTitle'=> ['type' => 'text', 'label' => 'Titel'],
            'content' => ['type' => 'richtext', 'label' => 'Tekst'],
        ];
    }

    public static function render(array $data): string {
        $content = htmlspecialchars($data['content'] ?? '');
        return "
        <section class='textarea-block'>
        h2>{$data['contentTitle']}</h2>
        <p>{$content}</p>
        </section>";
    }
}