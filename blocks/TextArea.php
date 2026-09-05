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
        $textArea = htmlspecialchars($data['content'] ?? 'Skriv den fulde tekst her...  ');
        $title = htmlspecialchars($data['contentTitle'] ?? 'Skriv en titel her...');
        return "
        <section class='textarea-block'>
        <h2>{$title}</h2>
        <p>{$textArea}</p>
        </section>";
    }
}