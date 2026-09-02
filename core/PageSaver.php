<?php
declare(strict_types=1);

/**
 * Gemmer en hel side i ét kald.
 *
 * Editoren holder sidens tilstand i browseren og sender den samlet, når
 * brugeren trykker Gem. Denne klasse tager imod og skriver den til
 * databasen — ændrede blokke, nye blokke og slettede blokke på én gang.
 *
 * ALT ELLER INTET
 * Det hele sker i én transaktion. Uden den kunne fem ud af otte blokke
 * blive gemt, hvorefter brugeren står med en side, der hverken er den
 * gamle eller den nye — og ikke har nogen måde at komme tilbage på.
 *
 * TILLID
 * Serveren stoler ikke på det, browseren sender, selv om det er
 * brugerens egen maskine. Bloktyper slås op i registryet, felter
 * filtreres gennem blokkens skema, og værdier valideres. Det, der ikke
 * passer, ryger ud i stedet for at blive skrevet ned.
 */
final class PageSaver
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PageRepository $pages,
        private readonly BlockRepository $blocks
    ) {
    }

    /**
     * @param array<string, mixed> $payload Afkodet JSON fra editoren.
     * @return array{blocks: int, deleted: int}
     *
     * @throws InvalidArgumentException ved ugyldigt input fra brugeren.
     */
    public function save(int $pageId, array $payload): array
    {
        $page = $this->pages->find($pageId);

        if ($page === null) {
            throw new InvalidArgumentException('Siden findes ikke.');
        }

        $pageData     = is_array($payload['page'] ?? null) ? $payload['page'] : [];
        $incoming     = is_array($payload['blocks'] ?? null) ? $payload['blocks'] : [];
        $existingIds  = $this->blocks->idsForPage($pageId);
        $keptIds      = [];

        $this->pdo->beginTransaction();

        try {
            $this->savePageSettings($pageId, $page, $pageData);

            foreach (array_values($incoming) as $position => $block) {
                if (!is_array($block)) {
                    continue;
                }

                $saved = $this->saveBlock($pageId, $block, ($position + 1) * 10, $existingIds);

                if ($saved !== null) {
                    $keptIds[] = $saved;
                }
            }

            // Blokke der findes i databasen, men ikke i det browseren
            // sendte, er dem brugeren har slettet i editoren.
            $removed = array_values(array_diff($existingIds, $keptIds));
            $this->blocks->deleteMany($pageId, $removed);

            $this->pdo->commit();

            return ['blocks' => count($keptIds), 'deleted' => count($removed)];

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $input
     */
    private function savePageSettings(int $pageId, array $current, array $input): void
    {
        $title = trim((string) ($input['title'] ?? $current['title']));
        $slug  = trim((string) ($input['slug'] ?? $current['slug']));
        $status = (string) ($input['status'] ?? $current['status']);

        if ($title === '') {
            throw new InvalidArgumentException('Siden skal have en titel.');
        }

        if (!Slug::isValid($slug)) {
            throw new InvalidArgumentException(
                'Ugyldig webadresse. Brug kun små bogstaver, tal og bindestreg.'
            );
        }

        // ENUM'en i databasen ville afvise andet, men en tydelig besked
        // her er bedre end en rå databasefejl.
        if (!in_array($status, ['draft', 'published'], true)) {
            throw new InvalidArgumentException('Ugyldig status.');
        }

        // Sluggen må ikke kollidere med en anden side under samme forælder.
        $clash = $this->pages->findBySlug($slug, $current['parent_id'] ?? null);

        if ($clash !== null && (int) $clash['id'] !== $pageId) {
            throw new InvalidArgumentException(
                "Webadressen '{$slug}' er allerede i brug af en anden side."
            );
        }

        $this->pages->update($pageId, $title, $slug, $status);
    }

    /**
     * Gemmer én blok. Returnerer blokkens id, eller null hvis den blev
     * afvist.
     *
     * @param array<string, mixed> $block
     * @param array<int, int>      $existingIds
     */
    private function saveBlock(
        int $pageId,
        array $block,
        int $sortOrder,
        array $existingIds
    ): ?int {
        $type  = (string) ($block['type'] ?? '');
        $class = BlockRegistry::get($type);

        // Ukendt bloktype afvises. Det er allowlisten, der forhindrer,
        // at browseren kan opfinde en bloktype.
        if ($class === null) {
            return null;
        }

        // Kun felter, skemaet kender, kommer med — og hver værdi tjekkes
        // mod sin felttype. Det er her et forsøg på at gemme
        // 'red; background:url(evil)' som farve bliver til standardværdien.
        $settings = FieldValidator::validateAll(
            $class::getSchema(),
            is_array($block['settings'] ?? null) ? $block['settings'] : []
        );

        $styles = FieldValidator::validateAll(
            $class::getStyleSchema(),
            is_array($block['styles'] ?? null) ? $block['styles'] : []
        );

        $id = isset($block['id']) ? (int) $block['id'] : 0;

        // Et id, der ikke i forvejen hører til denne side, behandles som
        // en ny blok. Så kan et manipuleret id ikke overskrive en blok
        // på en anden side.
        if ($id > 0 && in_array($id, $existingIds, true)) {
            $this->blocks->update($id, $pageId, $settings, $styles, $sortOrder);
            return $id;
        }

        return $this->blocks->insert($pageId, $type, $settings, $styles, $sortOrder);
    }
}