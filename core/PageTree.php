<?php
declare(strict_types=1);

/**
 * Ordner en flad liste af sider som et træ.
 *
 * Databasen giver os sider sorteret efter sort_order, men uden struktur:
 * en underside står bare et sted i listen. Tre steder har brug for at se
 * hierarkiet — oversigten "Dine sider" og de to dropdowns, hvor man vælger
 * en forælder — og derfor ligger logikken her frem for at være skrevet
 * tre gange.
 *
 * Resultatet er stadig en flad liste, men i træets rækkefølge og med et
 * dybde-tal på hver side. Det er nemmere at løbe igennem i en template
 * end en indlejret struktur, og indrykningen bliver et spørgsmål om at
 * gange dybden med en afstand.
 */
final class PageTree
{
    /** Beskytter mod en cyklisk forældrekæde, som databasen tillader. */
    private const MAX_DEPTH = 20;

    private function __construct()
    {
    }

    /**
     * @param array<int, array<string, mixed>> $pages Alle sider, sorteret.
     * @return array<int, array<string, mixed>> Samme sider med 'depth' tilføjet.
     */
    public static function flatten(array $pages): array
    {
        // Grupperes efter forælder, så hvert niveau kan hentes direkte
        // frem for at løbe hele listen igennem for hver enkelt side.
        $children = [];

        foreach ($pages as $page) {
            $parentId = $page['parent_id'] !== null ? (int) $page['parent_id'] : 0;
            $children[$parentId][] = $page;
        }

        return self::collect($children, 0, 0);
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $children
     * @return array<int, array<string, mixed>>
     */
    private static function collect(array $children, int $parentId, int $depth): array
    {
        if ($depth > self::MAX_DEPTH || !isset($children[$parentId])) {
            return [];
        }

        $result = [];

        foreach ($children[$parentId] as $page) {
            $page['depth'] = $depth;
            $result[]      = $page;

            // Undersider følger umiddelbart efter deres forælder, så
            // listen læses ovenfra og ned som et træ.
            foreach (self::collect($children, (int) $page['id'], $depth + 1) as $child) {
                $result[] = $child;
            }
        }

        return $result;
    }

    /**
     * Sider til en forælder-dropdown, med indrykning i selve teksten.
     *
     * @param array<int, array<string, mixed>> $pages
     * @param array<int, int>                  $exclude Id'er der ikke må vælges.
     * @return array<int, string> Side-id => vist tekst.
     */
    public static function choices(array $pages, array $exclude = []): array
    {
        $choices = [];

        foreach (self::flatten($pages) as $page) {
            $id = (int) $page['id'];

            if (in_array($id, $exclude, true)) {
                continue;
            }

            // Hårde mellemrum, fordi en select ikke respekterer CSS-
            // indrykning af enkelte options på tværs af browsere.
            $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}", (int) $page['depth']);

            $choices[$id] = $indent . ($page['depth'] > 0 ? '└ ' : '')
                . (string) $page['title'];
        }

        return $choices;
    }
}