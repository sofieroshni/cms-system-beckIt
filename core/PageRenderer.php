<?php
declare(strict_types=1);

/**
 * Syr blokke sammen til færdig HTML.
 *
 * Rendereren rører ikke databasen. Den tager en færdig liste af blokke ind
 * og giver HTML ud — hverken mere eller mindre.
 *
 * Det er en bevidst ændring fra den tidligere version, som tog en
 * databaseforbindelse ind og skrev sin egen query. Ud over at bryde
 * adskillelsen mellem data og præsentation gjorde det forhåndsvisning
 * umulig: rendereren kunne kun tegne det, der allerede stod i databasen.
 * Nu er den ligeglad med, hvor blokkene kommer fra, og kan derfor lige så
 * godt tegne en ugemt kladde fra en formular.
 */
final class PageRenderer
{
    private function __construct()
    {
    }

    /**
     * Tegner en række blokke til HTML.
     *
     * @param array<int, array<string, mixed>> $blocks Rækker fra BlockRepository.
     */
    public static function renderBlocks(
        array $blocks,
        RenderContext $context,
        bool $withEditorChrome = false
    ): string {
        $html = '';

        foreach ($blocks as $block) {
            $rendered = self::renderBlock($block, $context);

            if ($rendered === null) {
                continue;
            }

            $html .= $withEditorChrome
                ? self::wrapForEditor($block, $rendered)
                : $rendered;
        }

        return $html;
    }

    /**
     * Tegner én blok. Returnerer null, hvis bloktypen er ukendt.
     *
     * @param array<string, mixed> $block
     */
    public static function renderBlock(array $block, RenderContext $context): ?string
    {
        $type  = (string) ($block['block_type'] ?? '');
        $class = BlockRegistry::get($type);

        // Ukendt bloktype springes over frem for at vælte hele siden.
        // Kan fx ske, hvis en blok er fjernet fra registryet, mens der
        // stadig ligger instanser af den i databasen.
        if ($class === null) {
            return null;
        }

        // Værdierne normaliseres mod blokkens NUVÆRENDE skema.
        // Er der tilføjet et felt, siden blokken blev gemt, får den
        // standardværdien i stedet for at mangle nøglen og udløse en fejl.
        $settings = FieldValidator::validateAll(
            $class::getSchema(),
            is_array($block['settings'] ?? null) ? $block['settings'] : []
        );

        $styles = FieldValidator::validateAll(
            $class::getStyleSchema(),
            is_array($block['styles'] ?? null) ? $block['styles'] : []
        );

        return $class::render($settings, $styles, $context);
    }

    /**
     * Bygger et komplet HTML-dokument.
     *
     * Bruges både til forhåndsvisning og til de statiske filer, der
     * uploades via FTP i fase 7.
     *
     * @param array<string, mixed>             $page
     * @param array<int, array<string, mixed>> $blocks
     */
    public static function renderDocument(
        array $page,
        array $blocks,
        RenderContext $context
    ): string {
        $title       = (string) ($page['title'] ?? 'Uden titel');
        $stylesheets = self::stylesheets($blocks);

        $head = '';
        foreach ($stylesheets as $path) {
            $head .= '    <link rel="stylesheet" href="'
                . e($context->asset($path)) . '">' . PHP_EOL;
        }

        return '<!DOCTYPE html>' . PHP_EOL
            . '<html lang="da">' . PHP_EOL
            . '<head>' . PHP_EOL
            . '    <meta charset="UTF-8">' . PHP_EOL
            . '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL
            . '    <title>' . e($title) . '</title>' . PHP_EOL
            . '    <link rel="preconnect" href="https://fonts.googleapis.com">' . PHP_EOL
            . '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . PHP_EOL
            . '    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700'
            . '&family=Oleo+Script:wght@400;700&family=Roboto:wght@400;700&display=swap"'
            . ' rel="stylesheet">' . PHP_EOL
            . $head
            . '</head>' . PHP_EOL
            . '<body>' . PHP_EOL
            . self::renderBlocks($blocks, $context)
            . '</body>' . PHP_EOL
            . '</html>' . PHP_EOL;
    }

    /**
     * Hvilke stylesheets siden har brug for.
     *
     * Kun CSS for de bloktyper, der faktisk optræder på siden. En side med
     * to blokke henter ikke styling til de tyve, den ikke bruger.
     *
     * Eksporten i fase 7 bruger samme liste til at vide, hvilke filer der
     * skal med op på serveren.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array<int, string> Stier relative til projektroden.
     */
    public static function stylesheets(array $blocks): array
    {
        // Fælles grundlag først, så blokkenes egen CSS kan overskrive det.
        $sheets = ['assets/css/base.css'];

        foreach ($blocks as $block) {
            $path = self::stylesheetFor((string) ($block['block_type'] ?? ''));

            if ($path !== null && !in_array($path, $sheets, true)) {
                $sheets[] = $path;
            }
        }

        return $sheets;
    }

    /**
     * Stylesheets for ALLE registrerede bloktyper.
     *
     * Editoren bruger denne frem for stylesheets(), fordi brugeren når som
     * helst kan tilføje en hvilken som helst bloktype. Hentede editoren kun
     * CSS for de blokke, der allerede lå på siden, ville en nytilføjet blok
     * stå ustylet, indtil siden blev genindlæst.
     *
     * Den offentlige side og eksporten bruger stadig stylesheets(), så
     * besøgende kun henter det, siden faktisk bruger.
     *
     * @return array<int, string>
     */
    public static function allStylesheets(): array
    {
        $pseudoBlocks = array_map(
            static fn (string $type): array => ['block_type' => $type],
            array_keys(BlockRegistry::all())
        );

        return self::stylesheets($pseudoBlocks);
    }

    /**
     * Finder block.css i blokkens egen mappe.
     * Returnerer null, hvis blokken ikke har sin egen CSS.
     */
    private static function stylesheetFor(string $type): ?string
    {
        $class = BlockRegistry::get($type);

        if ($class === null) {
            return null;
        }

        $directory = dirname((new ReflectionClass($class))->getFileName());

        if (!is_file($directory . '/block.css')) {
            return null;
        }

        // Absolut sti gøres relativ til projektroden, så den kan bruges
        // både som URL i editoren og som filsti ved eksport.
        return str_replace('\\', '/', substr($directory, strlen(APP_ROOT) + 1))
            . '/block.css';
    }

    /**
     * Lægger editorens ramme og knapper omkring en blok.
     *
     * Selve blokkens HTML er identisk med den, brugeren får at se på den
     * færdige side — kun indpakningen adskiller sig. Det er dét, der gør
     * editoren til en reel forhåndsvisning.
     *
     * @param array<string, mixed> $block
     */
    private static function wrapForEditor(array $block, string $html): string
    {
        $id     = (int) ($block['id'] ?? 0);
        $type   = (string) ($block['block_type'] ?? '');
        $class  = BlockRegistry::get($type);
        $label  = $class !== null ? $class::label() : $type;
        $hidden = ($block['is_visible'] ?? true) ? '' : ' editor-block--hidden';

        return '<div class="editor-block' . $hidden . '"'
            . ' data-block-id="' . $id . '"'
            . ' data-block-type="' . e($type) . '">' . PHP_EOL
            . '    <span class="editor-block__label">' . e($label) . '</span>' . PHP_EOL
            . '    <div class="editor-block__actions">' . PHP_EOL
            . '        <button type="button" class="editor-btn editor-btn--edit"'
            . ' data-action="edit" aria-label="Rediger ' . e($label) . '">&#9998;</button>' . PHP_EOL
            . '        <button type="button" class="editor-btn editor-btn--delete"'
            . ' data-action="delete" aria-label="Slet ' . e($label) . '">&times;</button>' . PHP_EOL
            . '    </div>' . PHP_EOL
            . '    <div class="editor-block__preview">' . $html . '</div>' . PHP_EOL
            . '</div>' . PHP_EOL;
    }
}