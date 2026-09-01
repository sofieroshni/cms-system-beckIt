<?php
require_once __DIR__ . '/BlockInterface.php';

class NavbarBlock implements BlockInterface {
    // Beskriver hvilke felter admin-editoren skal vise, når man redigerer en Navbar-blok
    public static function getSchema(): array {
        return [
            'logo_image' => ['type' => 'image', 'label' => 'Logo'],
            'links'      => ['type' => 'text',  'label' => 'Menupunkter (format: Tekst|link, adskilt af komma)'],
        ];
    }

    // Genererer den faktiske HTML til den offentlige side
    public static function render(array $data): string {
        $logo = htmlspecialchars($data['logo_image'] ?? '');
        $raw  = $data['links'] ?? '';

        $items = '';
        foreach (explode(',', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '') continue;
            [$label, $url] = array_pad(explode('|', $pair, 2), 2, '#');
            $label = htmlspecialchars(trim($label));
            $url   = htmlspecialchars(trim($url));
            $items .= "<li><a href=\"{$url}\">{$label}</a></li>";
        }

        return "
            <nav class='navbar'>
                <div class='navbar-logo'><img src=\"{$logo}\" alt='Logo'></div>
                <ul class='navbar-links'>{$items}</ul>
            </nav>
            <style>
            .navbar {
                background-color: #1e2a6e;
                display: flex;
                align-items: center;
                gap: 20px;
                padding: 8px 16px;
                box-sizing: border-box;
                width: 100%;
            }
            .navbar-logo img {
                height: 36px;
                display: block;
            }
            .navbar-links {
                list-style: none;
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin: 0;
                padding: 0;
            }
            .navbar-links li a {
                color: white;
                text-decoration: none;
                font-size: 12px;
                white-space: nowrap;
            }
            .navbar-links li a:hover {
                text-decoration: underline;
            }
            </style>
        ";
    }
}