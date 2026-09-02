<?php
declare(strict_types=1);

/**
 * "Opret side" — brugeren vælger mellem en blank side og en skabelon.
 *
 * Filen viser kun formularen. Selve oprettelsen sker i store-page.php,
 * så et genindlæst vindue aldrig kan oprette den samme side to gange.
 */

require_once __DIR__ . '/../bootstrap.php';

$pdo       = Database::getConnection();
$templates = (new TemplateRepository($pdo))->findActive();

$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$error    = $_GET['fejl'] ?? null;
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opret side</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

<nav class="sidebar">
    <p class="sidebar__brand">Adminpanel</p>
    <ul class="sidebar__nav">
        <li><a href="index.php">Dine sider</a></li>
        <li><a href="create-page.php" aria-current="page">Opret side</a></li>
        <li><a href="export.php">Udgiv</a></li>
        <li><a href="#">Galleri</a></li>
        <li><a href="#">Indstillinger</a></li>
    </ul>
</nav>

<main class="content">
    <h1 class="content__title">Opret side</h1>

    <?php if ($error !== null): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="store-page.php" class="create-form">

        <div class="field">
            <label for="title">Sidens titel</label>
            <input type="text" id="title" name="title" required maxlength="255"
                   placeholder="Fx Forside eller Kontakt">
            <p class="field__hint">
                Adressen dannes automatisk ud fra titlen og kan rettes senere.
            </p>
        </div>

        <h2 class="section-heading">Vælg udgangspunkt</h2>

        <!--
            Radioknapperne er skjult visuelt, men findes stadig i DOM'en,
            så tastatur og skærmlæser fungerer. Kortene er deres labels.
        -->
        <div class="choices">

            <label class="choice">
                <input type="radio" name="template_id" value="0" checked>
                <span class="choice__body">
                    <span class="choice__thumb choice__thumb--blank" aria-hidden="true">+</span>
                    <span class="choice__title">Blank side</span>
                    <span class="choice__text">
                        Start helt forfra. Du tilføjer selv sektionerne bagefter.
                    </span>
                </span>
            </label>

            <?php foreach ($templates as $template): ?>
                <?php
                    $thumbnail = (string) ($template['thumbnail'] ?? '');
                    $hasThumb  = $thumbnail !== ''
                        && is_file(APP_ROOT . '/' . ltrim($thumbnail, '/'));
                ?>
                <label class="choice">
                    <input type="radio" name="template_id"
                           value="<?= (int) $template['id'] ?>">
                    <span class="choice__body">
                        <?php if ($hasThumb): ?>
                            <img class="choice__thumb"
                                 src="<?= e($basePath . '/' . ltrim($thumbnail, '/')) ?>"
                                 alt="">
                        <?php else: ?>
                            <span class="choice__thumb choice__thumb--empty" aria-hidden="true">
                                Ingen forhåndsvisning
                            </span>
                        <?php endif; ?>

                        <span class="choice__title"><?= e($template['name']) ?></span>
                        <span class="choice__text">
                            <?= e((string) ($template['description'] ?? '')) ?>
                        </span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if ($templates === []): ?>
            <p class="field__hint">
                Der er ingen skabeloner endnu. Du kan stadig oprette en blank side.
            </p>
        <?php endif; ?>

        <div class="actions">
            <a class="btn btn--ghost" href="index.php">Annullér</a>
            <button type="submit" class="btn btn--primary">Opret side</button>
        </div>
    </form>
</main>

</body>
</html>