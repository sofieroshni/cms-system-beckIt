<?php
declare(strict_types=1);

/**
 * "Delte blokke" — navbar, footer og andet, der går igen på tværs af sider.
 *
 * Redigeres ét sted og slår igennem overalt, hvor blokken er i brug.
 * Selve indsættelsen på en side sker i sideeditoren.
 */

require_once __DIR__ . '/../bootstrap.php';

$pdo    = Database::getConnection();
$shared = new SharedBlockRepository($pdo);
$pages  = new PageRepository($pdo);

$error = $_GET['fejl'] ?? null;

// Oprettelse og sletning er almindelige formularer med videresendelse.
// Kun selve redigeringen sker uden sideindlæsning, fordi den bruger de
// samme felter som editoren.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['handling'] ?? '') === 'opret') {
            $shared->create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['block_type'] ?? '')
            );
        } elseif (($_POST['handling'] ?? '') === 'slet') {
            $shared->delete((int) ($_POST['id'] ?? 0));
        }

        header('Location: shared.php');
        exit;

    } catch (InvalidArgumentException $e) {
        header('Location: shared.php?fejl=' . urlencode($e->getMessage()));
        exit;
    }
}

$blocks   = $shared->findAll();
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

$siteMap = SiteMap::fromPages($pages->findAll());
$context = RenderContext::editor($basePath, $siteMap);
$fields  = new FieldRenderer($siteMap->choices(), $basePath);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delte blokke</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="editor.css">

    <?php foreach (PageRenderer::allStylesheets() as $sheet): ?>
        <link rel="stylesheet" href="<?= e($basePath . '/' . $sheet) ?>">
    <?php endforeach; ?>
</head>
<body class="admin" data-base-path="<?= e($basePath) ?>">

<nav class="sidebar">
    <p class="sidebar__brand">Adminpanel</p>
    <ul class="sidebar__nav">
        <li><a href="index.php">Dine sider</a></li>
        <li><a href="create-page.php">Opret side</a></li>
        <li><a href="shared.php" aria-current="page">Delte blokke</a></li>
        <li><a href="export.php">Udgiv</a></li>
        <li><a href="#">Indstillinger</a></li>
    </ul>
</nav>

<main class="content">
    <h1 class="content__title">Delte blokke</h1>

    <?php if ($error !== null): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="panel">
        <h2>Sådan virker det</h2>
        <p>
            En delt blok findes kun ét sted. Retter du i den her, ændrer den sig
            på alle de sider, hvor den er sat ind — typisk menu og footer.
        </p>
        <p class="field__hint">
            Du sætter den ind på en side via <strong>+</strong> i sideeditoren.
        </p>
    </div>

    <div id="shared-list">
        <?php foreach ($blocks as $block): ?>
            <?php
                $class = BlockRegistry::get((string) $block['block_type']);
                if ($class === null) {
                    continue;
                }

                $settings = FieldValidator::validateAll($class::getSchema(), $block['settings']);
                $styles   = FieldValidator::validateAll($class::getStyleSchema(), $block['styles']);
                $usage    = $shared->usageCount((int) $block['id']);
            ?>
            <article class="ed-block shared-block" data-shared-id="<?= (int) $block['id'] ?>">
                <span class="ed-block__label"><?= e($class::label()) ?></span>

                <div class="ed-block__actions">
                    <button type="button" class="ed-btn ed-btn--edit" data-action="edit"
                            aria-expanded="false">&#9998;</button>

                    <form method="post" class="shared-block__delete"
                          onsubmit="return confirm('Slet den delte blok? Den fjernes fra <?= (int) $usage ?> side(r).');">
                        <input type="hidden" name="handling" value="slet">
                        <input type="hidden" name="id" value="<?= (int) $block['id'] ?>">
                        <button type="submit" class="ed-btn ed-btn--delete">&times;</button>
                    </form>
                </div>

                <p class="shared-block__meta">
                    <input type="text" class="shared-block__name" data-shared-name
                           value="<?= e($block['name']) ?>" maxlength="150"
                           aria-label="Navn på den delte blok">
                    <span class="shared-block__usage">
                        Bruges på <?= (int) $usage ?> side<?= $usage === 1 ? '' : 'r' ?>
                    </span>
                </p>

                <div class="ed-block__preview">
                    <?= $class::render($settings, $styles, $context) ?>
                </div>

                <?= $fields->panel($class, $settings, $styles) ?>

                <p class="shared-block__actions">
                    <span class="ed-status" data-shared-status role="status" aria-live="polite"></span>
                    <button type="button" class="btn btn--primary" data-action="save-shared">Gem</button>
                </p>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($blocks === []): ?>
        <p class="empty">Der er ingen delte blokke endnu.</p>
    <?php endif; ?>

    <h2 class="section-heading">Opret delt blok</h2>

    <form method="post" class="create-form">
        <input type="hidden" name="handling" value="opret">

        <div class="field">
            <label for="shared-name">Navn</label>
            <input type="text" id="shared-name" name="name" required maxlength="150"
                   placeholder="Fx Hovedmenu">
            <p class="field__hint">
                Navnet er kun til dig, så du kan skelne flere varianter fra hinanden.
            </p>
        </div>

        <div class="field">
            <label for="shared-type">Bloktype</label>
            <select id="shared-type" name="block_type">
                <?php foreach (BlockRegistry::all() as $type => $label): ?>
                    <option value="<?= e($type) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="field__hint">
                Bloktypen kan ikke ændres bagefter, da felterne hører til netop den type.
            </p>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn--primary">Opret</button>
        </div>
    </form>
</main>

<script src="shared.js"></script>
</body>
</html>