<?php
declare(strict_types=1);

/**
 * Oversætter en block_type-streng fra databasen til den PHP-klasse,
 * der kan tegne blokken.
 *
 * Det er systemets allowlist. En værdi fra databasen bliver ALDRIG brugt
 * til at bygge en filsti direkte — den slås op i kortet nedenfor, og
 * findes den ikke, sker der ingenting. Uden det trin ville en manipuleret
 * block_type kunne pege på en vilkårlig fil på serveren.
 *
 * At tilføje en ny bloktype er to trin: opret mappen under /blocks/,
 * og tilføj én linje i kortet. Ingen ændringer i renderer, editor eller
 * database.
 */
final class BlockRegistry
{
    /** @var array<string, class-string<BlockInterface>> */
    private const BLOCKS = [
        'hero'    => HeroBlock::class,
        'welcome' => WelcomeBlock::class,
    ];

    private function __construct()
    {
    }

    /**
     * @return class-string<BlockInterface>|null
     */
    public static function get(string $type): ?string
    {
        $class = self::BLOCKS[$type] ?? null;

        // Klassen indlæses af autoloaderen på dette tidspunkt.
        // Findes filen ikke, må vi hellere svare "ukendt blok" end at
        // lade en fatal fejl vælte hele siden.
        if ($class === null || !class_exists($class)) {
            return null;
        }

        return $class;
    }

    public static function exists(string $type): bool
    {
        return self::get($type) !== null;
    }

    /**
     * Alle bloktyper med deres visningsnavn. Bruges til "+"-menuen i
     * editoren, så listen dér altid matcher, hvad systemet rent faktisk
     * kan tegne.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $result = [];

        foreach (array_keys(self::BLOCKS) as $type) {
            $class = self::get($type);

            if ($class !== null) {
                $result[$type] = $class::label();
            }
        }

        return $result;
    }
}