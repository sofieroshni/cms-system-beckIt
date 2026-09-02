<?php
require_once __DIR__ . '/../blocks/BlockInterface.php';
require_once __DIR__ . '/../blocks/Hero.php';
require_once __DIR__ . '/../blocks/TextArea.php';
require_once __DIR__ . '/../blocks/Gallery.php';
require_once __DIR__ . '/../blocks/Image.php';
require_once __DIR__ . '/../blocks/Navbar.php';

class BlockRegistry {
    // Kortet mellem "navnet" gemt i databasen og selve PHP-klassen
    private static array $blocks = [
        'hero'     => 'HeroBlock',
        'textarea' => 'TextAreaBlock',
        'gallery'  => 'GalleryBlock',
        'image'    => 'ImageBlock',
        'navbar'   => 'NavbarBlock',
    ];

    // Slår en enkelt bloktype op, fx BlockRegistry::get('hero') -> "HeroBlock"
    public static function get(string $type): ?string {
        return self::$blocks[$type] ?? null;
    }

    // Returnerer hele listen — bruges fx til at vise "+"-menuen med valgmuligheder
    public static function all(): array {
        return self::$blocks;
    }
}