<?php
declare(strict_types=1);

/**
 * Editoren.
 *
 * Sidens tilstand holdes i browseren. Serveren leverer udgangspunktet og
 * de formularfelter, hver bloktype har brug for; JavaScript holder styr
 * på hvad der er ændret og sender det hele samlet, når brugeren gemmer.
 *
 * Formularfelterne bygges her i PHP ud fra blokkenes skemaer — ikke i
 * JavaScript. Skemaet er sandheden om, hvilke felter der findes, og den
 * viden skal kun ligge ét sted.
 */

require_once __DIR__ . '/../bootstrap.php';

$pageId = filter_input(INPUT_GET, 'page_id', FILTER_VALIDATE_INT) ?: 0;

$pdo             = Database::getConnection();
$pageRepository  = new PageRepository($pdo);
$blockRepository = new BlockRepository($pdo);

$page = $pageRepository->find($pageId);

if ($page === null) {
    http_response_code(404);
    exit('Siden blev ikke fundet.');
}

$blocks   = $blockRepository->findByPage($pageId);
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

$allPages = $pageRepository->findAll();

// Sitets struktur skal med, for at menupunkter kan slå deres målside op.
$siteMap = SiteMap::fromPages($allPages);

// En side må ikke kunne vælge sig selv eller en af sine egne undersider
// som forælder — det ville gøre forældrekæden cyklisk. De sorteres fra
// her, så valget slet ikke kan træffes, frem for kun at blive afvist
// bagefter af PageSaver.
$parentChoices = PageTree::choices(
    $allPages,
    array_merge([$pageId], $pageRepository->descendantIds($pageId))
);
$context = RenderContext::editor($basePath, $siteMap);

// Feltrendereren kender listen over sider, så et side-felt kan tegnes
// som en dropdown frem for et tekstfelt, man kan stave forkert i.
$fields = new FieldRenderer($siteMap->choices(), $basePath);

?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rediger: <?= e($page['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="editor.css">

    <?php /*
        CSS for ALLE bloktyper — ikke kun dem, der ligger på siden nu.
        Brugeren kan tilføje en hvilken som helst blok uden at genindlæse,
        og dens styling skal være på plads i det øjeblik den dukker op.
    */ ?>
    <?php foreach (PageRenderer::allStylesheets() as $sheet): ?>
        <link rel="stylesheet" href="<?= e($basePath . '/' . $sheet) ?>">
    <?php endforeach; ?>
</head>
<body class="editor" data-page-id="<?= (int) $page['id'] ?>"
      data-base-path="<?= e($basePath) ?>">

<header class="ed-top">
    <a class="ed-back" href="index.php" aria-label="Tilbage til dine sider">&larr;</a>
    <h1 class="ed-title"><?= e($page['title']) ?></h1>
</header>

<section class="ed-settings">
    <p class="ed-field">
        <label for="page-title">Titel</label>
        <input type="text" id="page-title" data-page-field="title"
               value="<?= e($page['title']) ?>" maxlength="255">
    </p>
    <p class="ed-field">
        <label for="page-slug">Webadresse</label>
        <input type="text" id="page-slug" data-page-field="slug"
               value="<?= e($page['slug']) ?>" maxlength="255">
    </p>
    <p class="ed-field">
        <label for="page-parent">Underside af</label>
        <select id="page-parent" data-page-field="parent_id">
            <option value="0">— ingen (ligger i roden) —</option>
            <?php foreach ($parentChoices as $choiceId => $choiceLabel): ?>
                <option value="<?= (int) $choiceId ?>"
                    <?= (int) ($page['parent_id'] ?? 0) === (int) $choiceId ? 'selected' : '' ?>>
                    <?= e($choiceLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="ed-field">
        <label for="page-status">Status</label>
        <select id="page-status" data-page-field="status">
            <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Kladde</option>
            <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Udgivet</option>
        </select>
    </p>
</section>

<main class="ed-canvas" id="canvas">
    <?php foreach ($blocks as $block): ?>
        <?php
            $class = BlockRegistry::get((string) $block['block_type']);
            if ($class === null) {
                continue;
            }
            $settings = FieldValidator::validateAll($class::getSchema(), $block['settings']);
            $styles   = FieldValidator::validateAll($class::getStyleSchema(), $block['styles']);
        ?>
        <article class="ed-block"
                 data-block-id="<?= (int) $block['id'] ?>"
                 data-block-type="<?= e($block['block_type']) ?>">

            <span class="ed-block__label"><?= e($class::label()) ?></span>

            <div class="ed-block__actions">
                <button type="button" class="ed-btn ed-btn--edit" data-action="edit"
                        aria-expanded="false">&#9998;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="up">&and;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="down">&or;</button>
                <button type="button" class="ed-btn ed-btn--delete" data-action="delete">&times;</button>
            </div>

            <div class="ed-block__preview">
                <?= $class::render($settings, $styles, $context) ?>
            </div>

            <?= $fields->panel($class, $settings, $styles) ?>
        </article>
    <?php endforeach; ?>
</main>

<section class="ed-add">
    <button type="button" class="ed-add__toggle" id="add-toggle" aria-expanded="false">+</button>

    <div class="ed-add__menu" id="add-menu" hidden>
        <?php foreach (BlockRegistry::all() as $type => $label): ?>
            <button type="button" class="ed-add__choice" data-add-type="<?= e($type) ?>">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<footer class="ed-footer">
    <span class="ed-status" id="save-status" role="status" aria-live="polite"></span>
    <button type="button" class="btn btn--ghost" id="preview-btn">Forhåndsvis</button>
    <button type="button" class="btn btn--primary" id="save-btn" disabled>Gem</button>
</footer>

<?php
/*
 * Skabeloner til nye blokke.
 *
 * Hver bloktype ligger klar som en <template> med sit forhåndsvisning og
 * sine felter, udfyldt med standardværdier. Når brugeren tilføjer en blok,
 * kloner JavaScript den tilsvarende skabelon.
 *
 * Alternativet — at hente markup fra serveren ved hvert klik — ville koste
 * et netværkskald og en ekstra fil for præcis samme resultat.
 */
?>
<?php foreach (BlockRegistry::all() as $type => $label): ?>
    <?php
        $class    = BlockRegistry::get($type);
        $defaults = $class::defaultSettings();
        $dStyles  = $class::defaultStyles();
    ?>
    <template data-template-for="<?= e($type) ?>">
        <article class="ed-block" data-block-id="" data-block-type="<?= e($type) ?>">
            <span class="ed-block__label"><?= e($label) ?></span>
            <div class="ed-block__actions">
                <button type="button" class="ed-btn ed-btn--edit" data-action="edit"
                        aria-expanded="false">&#9998;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="up">&and;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="down">&or;</button>
                <button type="button" class="ed-btn ed-btn--delete" data-action="delete">&times;</button>
            </div>
            <div class="ed-block__preview">
                <?= $class::render($defaults, $dStyles, $context) ?>
            </div>
            <?= $fields->panel($class, $defaults, $dStyles) ?>
        </article>
    </template>
<?php endforeach; ?>

<script src="editor.js"></script>
</body>
</html>