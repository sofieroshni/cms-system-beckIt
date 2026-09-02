<?php
declare(strict_types=1);

/**
 * Fortæller en blok, HVOR den bliver renderet hen.
 *
 * Baggrund: det samme billede skal skrives forskelligt afhængigt af mål.
 * I editoren på localhost skal stien pege ind i projektmappen. I den
 * eksporterede, statiske hjemmeside skal den være relativ til den side,
 * filen ender i — og en underside i om-os/ skal pege et niveau op.
 *
 * Uden denne kontekst ville blokkene skulle gætte, og alle billeder ville
 * knække i det øjeblik en side flyttes ned under en forælder.
 */
final class RenderContext
{
    public const MODE_EDITOR = 'editor';
    public const MODE_EXPORT = 'export';

    private function __construct(
        public readonly string $mode,
        private readonly string $basePath
    ) {
    }

    /**
     * Til admin-editoren og forhåndsvisning på localhost.
     *
     * @param string $basePath Fx '/cms-system-beckIt'
     */
    public static function editor(string $basePath = ''): self
    {
        return new self(self::MODE_EDITOR, rtrim($basePath, '/'));
    }

    /**
     * Til eksport af statiske filer.
     *
     * @param int $depth Hvor mange mapper nede siden ligger.
     *                   Forside = 0, om-os/bestyrelse = 2.
     */
    public static function export(int $depth = 0): self
    {
        $basePath = $depth > 0 ? rtrim(str_repeat('../', $depth), '/') : '.';

        return new self(self::MODE_EXPORT, $basePath);
    }

    public function isEditor(): bool
    {
        return $this->mode === self::MODE_EDITOR;
    }

    /**
     * Oversætter en gemt filsti til en URL, der virker i denne kontekst.
     *
     * Stien i databasen er altid relativ til projektroden, fx
     * 'assets/demo/hero.jpg'. Aldrig absolut, aldrig med domæne.
     */
    public function asset(string $path): string
    {
        $path = ltrim($path, '/');

        if ($path === '') {
            return '';
        }

        return $this->basePath . '/' . $path;
    }
}