<?php
declare(strict_types=1);

/**
 * Bygger hele websitet som statiske filer.
 *
 * Resultatet er en mappe, der kan lægges på en hvilken som helst server:
 * HTML, CSS og de billeder, der faktisk bruges. Ingen PHP, ingen database.
 *
 * ÉN RENDERINGSVEJ
 * Eksporten kalder samme PageRenderer som editoren og forhåndsvisningen.
 * Forskellen er alene konteksten: i editoren peger stier absolut ind i
 * projektet, ved eksport peger de relativt i forhold til den mappe, filen
 * ender i. Havde eksporten sin egen renderer, ville de to uundgåeligt
 * skride fra hinanden.
 *
 * MAPPESTRUKTUR
 * Den første udgivne side i rodniveauet bliver til index.html. Alle andre
 * får deres egen mappe efter deres slug:
 *
 *     export/index.html                 (forsiden)
 *     export/kontakt/index.html
 *     export/om-os/bestyrelse/index.html
 *     export/assets/css/base.css
 *     export/blocks/hero/block.css
 *
 * Adressen bliver dermed /kontakt/ frem for /kontakt.html.
 */
final class SiteExporter
{
    /** @var array<int, string> Stier til filer, der skal kopieres med. */
    private array $assets = [];

    /** @var array<int, string> */
    private array $warnings = [];

    public function __construct(
        private readonly PageRepository $pages,
        private readonly BlockRepository $blocks,
        private readonly string $exportDir
    ) {
    }

    /**
     * Kører eksporten.
     *
     * @return array{pages: int, assets: int, warnings: array<int, string>, dir: string}
     */
    public function export(): array
    {
        $all = $this->pages->findAll();

        // Kun udgivne sider kommer med. Det er dét, der giver
        // kladde-status en reel betydning frem for kun at være en etiket.
        $published = array_values(array_filter(
            $all,
            static fn (array $page): bool => $page['status'] === 'published'
        ));

        if ($published === []) {
            throw new RuntimeException(
                'Ingen sider er udgivet. Sæt mindst én side til "Udgivet" først.'
            );
        }

        $this->assets   = [];
        $this->warnings = [];

        $this->prepareDirectory();

        // Slås op på id, så forældrekæden kan følges uden en query pr. side.
        $byId = [];
        foreach ($all as $page) {
            $byId[(int) $page['id']] = $page;
        }

        $frontPageId = $this->findFrontPage($published);
        $exported    = 0;

        foreach ($published as $page) {
            $this->exportPage($page, $byId, (int) $page['id'] === $frontPageId);
            $this->pages->markAsPublished((int) $page['id']);
            $exported++;
        }

        $this->copyAssets();

        return [
            'pages'    => $exported,
            'assets'   => count($this->assets),
            'warnings' => $this->warnings,
            'dir'      => $this->exportDir,
        ];
    }

    /**
     * Forsiden er den første udgivne side i rodniveauet.
     *
     * Rækkefølgen er den, brugeren selv har trukket sig frem til under
     * "Dine sider", så valget er synligt og kan ændres uden ny kode.
     *
     * @param array<int, array<string, mixed>> $published
     */
    private function findFrontPage(array $published): int
    {
        foreach ($published as $page) {
            if ($page['parent_id'] === null) {
                return (int) $page['id'];
            }
        }

        // Ingen udgivne rod-sider: første side bliver forside, så
        // websitet i det mindste har en index.html.
        return (int) $published[0]['id'];
    }

    /**
     * @param array<string, mixed>                 $page
     * @param array<int, array<string, mixed>>     $byId
     */
    private function exportPage(array $page, array $byId, bool $isFrontPage): void
    {
        $pageId = (int) $page['id'];
        $blocks = $this->blocks->findByPage($pageId, onlyVisible: true);

        // Forsiden ligger i roden; alle andre i deres egen mappe.
        $segments = $isFrontPage ? [] : $this->pathSegments($page, $byId);
        $depth    = count($segments);

        $context = RenderContext::export($depth);
        $html    = PageRenderer::renderDocument($page, $blocks, $context);

        // Stylesheets og billeder noteres, mens vi er her, så vi bagefter
        // kun kopierer det, der rent faktisk bruges.
        foreach (PageRenderer::stylesheets($blocks) as $sheet) {
            $this->noteAsset($sheet);
        }

        foreach ($blocks as $block) {
            $this->collectImages($block);
        }

        $directory = $this->exportDir . ($segments === [] ? '' : '/' . implode('/', $segments));

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Kunne ikke oprette mappen: {$directory}");
        }

        if (file_put_contents($directory . '/index.html', $html) === false) {
            throw new RuntimeException("Kunne ikke skrive: {$directory}/index.html");
        }
    }

    /**
     * Følger forældrekæden og bygger mappestien.
     *
     * @param array<string, mixed>             $page
     * @param array<int, array<string, mixed>> $byId
     * @return array<int, string>
     */
    private function pathSegments(array $page, array $byId): array
    {
        $segments = [(string) $page['slug']];
        $parentId = $page['parent_id'] !== null ? (int) $page['parent_id'] : null;

        // Loftet beskytter mod en cyklisk forældrekæde. Databasen tillader
        // den i teorien, og uden loftet ville eksporten hænge i en
        // uendelig løkke frem for at fejle tydeligt.
        $depth = 0;

        while ($parentId !== null && isset($byId[$parentId]) && $depth < 20) {
            $parent   = $byId[$parentId];
            $segments[] = (string) $parent['slug'];
            $parentId = $parent['parent_id'] !== null ? (int) $parent['parent_id'] : null;
            $depth++;
        }

        return array_reverse($segments);
    }

    /**
     * Finder billedfelter i en blok ved at gå dens skema igennem.
     *
     * Skemaet fortæller, hvilke felter der er billeder — så eksporten
     * behøver ikke kende de enkelte bloktyper. Tilføjer I en ny blok med
     * et billedfelt, kommer dens billeder automatisk med.
     *
     * @param array<string, mixed> $block
     */
    private function collectImages(array $block): void
    {
        $class = BlockRegistry::get((string) $block['block_type']);

        if ($class === null) {
            return;
        }

        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];

        foreach ($class::getSchema() as $name => $field) {
            $type = $field['type'] ?? 'text';

            if ($type === 'image' && !empty($settings[$name])) {
                $this->noteAsset((string) $settings[$name]);
                continue;
            }

            // Billeder kan også ligge i gentagne rækker, fx et galleri.
            if ($type === 'repeater' && is_array($settings[$name] ?? null)) {
                foreach ($settings[$name] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach ($field['fields'] ?? [] as $subName => $subField) {
                        if (($subField['type'] ?? '') === 'image' && !empty($row[$subName])) {
                            $this->noteAsset((string) $row[$subName]);
                        }
                    }
                }
            }
        }
    }

    private function noteAsset(string $path): void
    {
        $path = ltrim($path, '/');

        if ($path !== '' && !in_array($path, $this->assets, true)) {
            $this->assets[] = $path;
        }
    }

    /**
     * Kopierer CSS og billeder med, i samme mappestruktur som i projektet.
     *
     * Strukturen bevares, fordi de stier, blokkene skriver ud, er relative
     * til projektroden. Flyttede vi filerne, ville henvisningerne knække.
     */
    private function copyAssets(): void
    {
        foreach ($this->assets as $relativePath) {
            $source = APP_ROOT . '/' . $relativePath;

            if (!is_file($source)) {
                $this->warnings[] = "Manglende fil: {$relativePath}";
                continue;
            }

            $target    = $this->exportDir . '/' . $relativePath;
            $directory = dirname($target);

            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->warnings[] = "Kunne ikke oprette mappen til: {$relativePath}";
                continue;
            }

            if (!copy($source, $target)) {
                $this->warnings[] = "Kunne ikke kopiere: {$relativePath}";
            }
        }
    }

    /**
     * Tømmer eksportmappen, så filer fra en tidligere kørsel ikke bliver
     * hængende — fx en side, der siden er sat tilbage til kladde.
     */
    private function prepareDirectory(): void
    {
        // Sikkerhedsnet. Metoden sletter rekursivt, så den skal kun kunne
        // pege på en mappe, vi selv har udpeget til formålet.
        if (basename($this->exportDir) !== 'export'
            || !str_starts_with($this->exportDir, APP_ROOT)) {
            throw new RuntimeException('Ugyldig eksportmappe.');
        }

        if (is_dir($this->exportDir)) {
            $this->deleteContents($this->exportDir);
        } elseif (!mkdir($this->exportDir, 0775, true) && !is_dir($this->exportDir)) {
            throw new RuntimeException('Kunne ikke oprette eksportmappen.');
        }
    }

    private function deleteContents(string $directory): void
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            /** @var SplFileInfo $item */
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
    }
}