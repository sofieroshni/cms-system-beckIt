<?php
declare(strict_types=1);

/**
 * Fortæller en blok, HVOR den bliver renderet hen.
 *
 * Baggrund: den samme henvisning skal skrives forskelligt afhængigt af mål.
 * I editoren på localhost skal et billede pege ind i projektmappen. I den
 * eksporterede, statiske hjemmeside skal det være relativt til den fil,
 * indholdet ender i — og en underside i om-os/ skal pege et niveau op.
 *
 * Det gælder både filer (asset) og links mellem sider (pageUrl).
 */
final class RenderContext
{
    public const MODE_EDITOR = 'editor';
    public const MODE_EXPORT = 'export';

    private function __construct(
        public readonly string $mode,
        private readonly string $basePath,
        private readonly int $depth,
        private readonly ?SiteMap $siteMap
    ) {
    }

    /**
     * Til admin-editoren og forhåndsvisning på localhost.
     *
     * @param string $basePath Fx '/cms-system-beckIt'
     */
    public static function editor(string $basePath = '', ?SiteMap $siteMap = null): self
    {
        return new self(self::MODE_EDITOR, rtrim($basePath, '/'), 0, $siteMap);
    }

    /**
     * Til eksport af statiske filer.
     *
     * @param int $depth Hvor mange mapper nede siden ligger.
     *                   Forside = 0, om-os/bestyrelse = 2.
     */
    public static function export(int $depth = 0, ?SiteMap $siteMap = null): self
    {
        $basePath = $depth > 0 ? rtrim(str_repeat('../', $depth), '/') : '.';

        return new self(self::MODE_EXPORT, $basePath, $depth, $siteMap);
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

    /**
     * Adressen på en anden side i systemet.
     *
     * I editoren peger den på page.php, så forhåndsvisningen kan følges.
     * Ved eksport bliver den til en relativ sti mellem to mapper.
     *
     * Returnerer '#' — et dødt link — hvis siden ikke findes eller ikke er
     * udgivet. Et link til en fil, der ikke bliver eksporteret, ville give
     * den besøgende en 404.
     */
    public function pageUrl(int $pageId): string
    {
        if ($pageId <= 0 || $this->siteMap === null || !$this->siteMap->has($pageId)) {
            return '#';
        }

        if ($this->isEditor()) {
            return $this->basePath . '/page.php?id=' . $pageId;
        }

        if (!$this->siteMap->isPublished($pageId)) {
            return '#';
        }

        // Op til roden, og derfra ned i målets mappe.
        $up   = $this->depth > 0 ? str_repeat('../', $this->depth) : '';
        $path = $this->siteMap->path($pageId);

        if ($path === '') {
            // Forsiden. './' frem for tom streng, så href aldrig bliver tomt.
            return $up === '' ? './' : $up;
        }

        return $up . $path . '/';
    }
}