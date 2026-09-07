<?php
require_once __DIR__ . '/../blocks/BlockInterface.php';
require_once __DIR__ . '/../blocks/Hero.php';
require_once __DIR__ . '/../blocks/TextAreaBlue.php';
require_once __DIR__ . '/../blocks/Gallery.php';
require_once __DIR__ . '/../blocks/Image.php';
require_once __DIR__ . '/../blocks/Navbar.php';
require_once __DIR__ . '/../blocks/TextAreaTwoPictures.php';
require_once __DIR__ . '/../blocks/Tabel.php';
require_once __DIR__ . '/../blocks/Cards.php';

//Spørg om hvilke blokke der som udgangspunkt skal være


class BlockRegistry {
    // Kortet mellem "navnet" gemt i databasen og selve PHP-klassen
    private static array $blocks = [
        'hero'     => 'HeroBlock',
        'textareablue' => 'TextAreaBlueBlock',
        'textareatwopictures' => 'TextAreaTwoPicturesBlock',
        'gallery'  => 'GalleryBlock',
        'image'    => 'ImageBlock',
        'navbar'   => 'NavbarBlock',
        'tabel' => 'TabelBlock',
         'cards' => 'CardsBlock'

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