<?php
declare(strict_types=1);

/**
 * Opretter sider — blanke eller ud fra en skabelon.
 *
 * Ligger som en selvstændig service, fordi opgaven rører flere tabeller
 * på én gang og derfor ikke hører hjemme i ét enkelt repository.
 *
 * MATERIALISERING
 * Når en side oprettes fra en skabelon, KOPIERES skabelonens blokke ned i
 * page_blocks. Siden gemmer ingen levende reference til skabelonen.
 *
 * Alternativet — at siden peger på skabelonen og henter sine blokke
 * derfra — lyder mere elegant, men bryder sammen i samme øjeblik brugeren
 * ændrer indholdet. Og det gør det umuligt at svare på, hvad der sker med
 * hundrede eksisterende sider, når nogen retter i skabelonen.
 * Med kopiering er svaret altid: ingenting.
 */
final class PageBuilder
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PageRepository $pages,
        private readonly BlockRepository $blocks,
        private readonly TemplateRepository $templates
    ) {
    }

    /**
     * Opretter en tom side uden blokke.
     */
    public function createBlank(string $title, ?int $parentId = null): int
    {
        $title = $this->cleanTitle($title);

        return $this->pages->create(
            $title,
            $this->uniqueSlug($title, $parentId),
            $parentId
        );
    }

    /**
     * Opretter en side og kopierer skabelonens blokke ned på den.
     *
     * @throws InvalidArgumentException hvis skabelonen ikke findes.
     */
    public function createFromTemplate(
        int $templateId,
        string $title,
        ?int $parentId = null
    ): int {
        $template = $this->templates->find($templateId);

        if ($template === null) {
            throw new InvalidArgumentException('Skabelonen findes ikke.');
        }

        $title = $this->cleanTitle($title);
        $slug  = $this->uniqueSlug($title, $parentId);

        // Alt eller intet. Uden transaktionen kunne en fejl midtvejs
        // efterlade en side med halvdelen af sine blokke — hvilket for
        // brugeren bare ligner, at skabelonen er i stykker.
        $this->pdo->beginTransaction();

        try {
            $pageId = $this->pages->create(
                $title,
                $slug,
                $parentId,
                'draft',
                $templateId
            );

            foreach ($this->templates->findBlocks($templateId) as $blockData) {
                $type  = (string) $blockData['block_type'];
                $class = BlockRegistry::get($type);

                // Skabelonen kan referere til en bloktype, der siden er
                // fjernet fra registryet. Spring den over frem for at
                // afvise hele oprettelsen.
                if ($class === null) {
                    error_log("Skabelon {$templateId}: ukendt bloktype '{$type}' sprunget over.");
                    continue;
                }

                // Skabelondata valideres på præcis samme måde som
                // brugerinput. Så er data i page_blocks garanteret
                // ensartet, uanset om det kom fra en redaktør eller fra
                // en seed-fil skrevet for et år siden.
                $settings = FieldValidator::validateAll(
                    $class::getSchema(),
                    $blockData['settings']
                );

                $styles = FieldValidator::validateAll(
                    $class::getStyleSchema(),
                    $blockData['styles']
                );

                $this->blocks->insert(
                    $pageId,
                    $type,
                    $settings,
                    $styles,
                    $blockData['sort_order']
                );
            }

            $this->pdo->commit();

            return $pageId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function cleanTitle(string $title): string
    {
        $title = trim($title);

        return $title === '' ? 'Ny side' : mb_substr($title, 0, 255, 'UTF-8');
    }

    /**
     * Finder en ledig slug.
     *
     * Databasen har en unik nøgle på (forælder + slug), så to sider med
     * samme titel ville ellers give en fejl, brugeren ikke kan gøre noget
     * ved. I stedet tilføjes et tal: kontakt, kontakt-2, kontakt-3.
     */
    private function uniqueSlug(string $title, ?int $parentId): string
    {
        $base = Slug::fromTitle($title);
        $slug = $base;

        for ($suffix = 2; $suffix < 100; $suffix++) {
            if ($this->pages->findBySlug($slug, $parentId) === null) {
                return $slug;
            }
            $slug = $base . '-' . $suffix;
        }

        // Nødudgang, hvis nogen har hundrede sider med samme titel.
        return $base . '-' . bin2hex(random_bytes(3));
    }
}