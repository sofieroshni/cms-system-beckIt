<?php
declare(strict_types=1);

/**
 * "Dine sider" — oversigten over alle sider.
 *
 * Træk-og-slip til at ændre rækkefølgen kommer i fase 6 sammen med
 * resten af gemme-flowet. Markup'en er allerede forberedt til det:
 * hver række har et data-page-id og et greb.
 */

require_once __DIR__ . '/../bootstrap.php';

$pdo   = Database::getConnection();
$pages = (new PageRepository($pdo))->findAll();

$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dine sider</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin">

<nav class="sidebar">
    <p class="sidebar__brand">Adminpanel</p>
    <ul class="sidebar__nav">
        <li><a href="index.php" aria-current="page">Dine sider</a></li>
        <li><a href="create-page.php">Opret side</a></li>
        <li><a href="export.php">Udgiv</a></li>
        <li><a href="#">Galleri</a></li>
        <li><a href="#">Indstillinger</a></li>
    </ul>
</nav>

<main class="content">
    <h1 class="content__title">Dine sider</h1>

    <?php /* Fejl fra delete-page.php, fx naar en side har undersider. */ ?>
    <?php if (isset($_GET['fejl'])): ?>
        <p class="alert" role="alert"><?= e((string) $_GET['fejl']) ?></p>
    <?php endif; ?>

    <ul class="page-list" id="page-list">
        <?php foreach ($pages as $index => $page): ?>
            <li class="page-row" data-page-id="<?= (int) $page['id'] ?>">
                <span class="page-row__handle" aria-hidden="true">⠿</span>

                <span class="page-row__title"><?= e($page['title']) ?></span>

                <span class="badge badge--<?= e($page['status']) ?>">
                    <?= $page['status'] === 'published' ? 'udgivet' : 'kladde' ?>
                </span>

                <a class="icon-btn icon-btn--view"
                   href="<?= e($basePath) ?>/page.php?id=<?= (int) $page['id'] ?>"
                   target="_blank" rel="noopener"
                   title="Se siden">&#128065;</a>

                <a class="icon-btn icon-btn--edit"
                   href="editor.php?page_id=<?= (int) $page['id'] ?>"
                   title="Rediger">&#9998;</a>

                <!--
                    Sletning sker via POST, ikke via et link. Et link kan
                    følges af browserens forudindlæsning eller en
                    historik-knap, og så er siden væk uden at nogen
                    trykkede på noget.
                -->
                <form method="post" action="delete-page.php" class="page-row__delete"
                      onsubmit="return confirm('Slet siden &quot;<?= e($page['title']) ?>&quot;? Det kan ikke fortrydes.');">
                    <input type="hidden" name="page_id" value="<?= (int) $page['id'] ?>">
                    <button type="submit" class="icon-btn icon-btn--delete"
                            title="Slet">&#128465;</button>
                </form>

                <span class="page-row__order"><?= $index + 1 ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($pages === []): ?>
        <p class="empty">Du har ingen sider endnu.</p>
    <?php endif; ?>

    <p class="list-status" id="list-status" role="status" aria-live="polite"></p>

    <a class="create-link" href="create-page.php">opret side <span aria-hidden="true">+</span></a>
</main>

<script src="admin.js"></script>
</body>
</html>