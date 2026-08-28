<?php
require_once __DIR__ . '/BlockInterface.php';

class HeroBlock implements BlockInterface {
    // Beskriver hvilke felter admin-editoren skal vise, når man redigerer en Hero-blok
    public static function getSchema(): array {
        return [
            'title'    => ['type' => 'text',  'label' => 'Overskrift'],
            'address'  => ['type' => 'text',  'label' => 'Adresse'],
            'phone'    => ['type' => 'text',  'label' => 'Telefon'],
            'bg_image' => ['type' => 'image', 'label' => 'Baggrundsbillede'],
        ];
    }

    // Genererer den faktiske HTML til den offentlige side
    public static function render(array $data): string {
        $title   = htmlspecialchars($data['title']   ?? '');
        $address = htmlspecialchars($data['address'] ?? '');
        $phone   = htmlspecialchars($data['phone']   ?? '');
        $bg      = htmlspecialchars($data['bg_image'] ?? '');

        return "
            <section class='hero' style=\"background-image:url('{$bg}')\">
                <div class='hero-box'>
                    <h1>{$title}</h1>
                    <span>{$address}</span>
                    <span>{$phone}</span>
                </div>
            </section>
        ";
    }
}