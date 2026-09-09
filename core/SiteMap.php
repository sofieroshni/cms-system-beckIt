<?php
declare(strict_types=1);

/**
 * Sitets struktur: hvilken adresse hver side får, og hvilken der er forside.
 *
 * Findes, fordi et link mellem to sider ikke kan skrives som fast tekst.
 * Adressen på kontaktsiden er "kontakt/" set fra forsiden, men "../kontakt/"
 * set fra en underside. Uden et sted at slå strukturen op ville hver blok
 * skulle gætte — og alle links ville knække i det øjeblik en side blev
 * flyttet ned under en forælder.
 *
 * Klassen er også det ene sted, der afgør, hvilken side der er forside.
 * Både editoren, eksporten og udgivelsesskærmen spørger her, så de tre
 * aldrig kan blive uenige.
 */
final class SiteMap
{
    /** @var array<int, array<int, string>> Side-id => stiens mappenavne. */
    private array $segments = [];

    /** @var array<int, string> Side-id => titel. */
    private array $titles = [];

    /** @var array<int, bool> Side-id => er udgivet. */
    private array $published = [];

    private int $frontPageId = 0;

    /**
     * @param array<int, array<string, mixed>> $pages Alle sider fra PageRepository.
     */
    public static function fromPages(array $pages): self
    {
        $map  = new self();
        $byId = [];

        foreach ($pages as $page) {
            $byId[(int) $page['id']] = $page;
        }

        foreach ($pages as $page) {
            $id = (int) $page['id'];

            $map->titles[$id]    = (string) $page['title'];
            $map->published[$id] = $page['status'] === 'published';
            $map->segments[$id]  = self::buildSegments($page, $byId);
        }

        $map->frontPageId = self::findFrontPage($pages);

        return $map;
    }

    /**
     * Forsiden er den øverste udgivne side i rodniveauet — altså den,
     * brugeren selv har trukket til toppen af "Dine sider".
     *
     * @param array<int, array<string, mixed>> $pages
     */
    private static function findFrontPage(array $pages): int
    {
        foreach ($pages as $page) {
            if ($page['status'] === 'published' && $page['parent_id'] === null) {
                return (int) $page['id'];
            }
        }

        foreach ($pages as $page) {
            if ($page['status'] === 'published') {
                return (int) $page['id'];
            }
        }

        return 0;
    }

    /**
     * Følger forældrekæden og bygger stiens mappenavne.
     *
     * @param array<string, mixed>             $page
     * @param array<int, array<string, mixed>> $byId
     * @return array<int, string>
     */
    private static function buildSegments(array $page, array $byId): array
    {
        $segments = [(string) $page['slug']];
        $parentId = $page['parent_id'] !== null ? (int) $page['parent_id'] : null;

        // Loftet beskytter mod en cyklisk forældrekæde. Databasen tillader
        // den i teorien, og uden loftet ville vi løbe i ring frem for at
        // fejle tydeligt.
        $depth = 0;

        while ($parentId !== null && isset($byId[$parentId]) && $depth < 20) {
            $parent     = $byId[$parentId];
            $segments[] = (string) $parent['slug'];
            $parentId   = $parent['parent_id'] !== null ? (int) $parent['parent_id'] : null;
            $depth++;
        }

        return array_reverse($segments);
    }

    public function isFrontPage(int $pageId): bool
    {
        return $pageId === $this->frontPageId && $pageId !== 0;
    }

    public function isPublished(int $pageId): bool
    {
        return $this->published[$pageId] ?? false;
    }

    public function has(int $pageId): bool
    {
        return isset($this->segments[$pageId]);
    }

    /**
     * Sidens mappenavne. Forsiden har ingen — den ligger i roden.
     *
     * @return array<int, string>
     */
    public function segments(int $pageId): array
    {
        if ($this->isFrontPage($pageId)) {
            return [];
        }

        return $this->segments[$pageId] ?? [];
    }

    /**
     * Sidens adresse uden ledende og afsluttende skråstreg.
     * Forsiden giver en tom streng.
     */
    public function path(int $pageId): string
    {
        return implode('/', $this->segments($pageId));
    }

    /**
     * Sider til dropdown-listen i editoren.
     *
     * @return array<int, string> Side-id => titel.
     */
    public function choices(): array
    {
        return $this->titles;
    }

    public function title(int $pageId): string
    {
        return $this->titles[$pageId] ?? '';
    }
}