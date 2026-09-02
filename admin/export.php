<?php
declare(strict_types=1);

/**
 * "Udgiv" — bygger websitet som statiske filer.
 *
 * Både visning og kørsel ligger i samme fil, fordi resultatet skal vises
 * bagefter. Eksporten sker kun ved POST, så et genindlæst vindue ikke
 * bygger sitet igen.
 */

require_once __DIR__ . '/../bootstrap.php';

$pdo            = Database::getConnection();
$pageRepository = new PageRepository($pdo);

$allPages  = $pageRepository->findAll();
$published = array_filter(
    $allPages,
    static fn (array $page): bool => $page['status'] === 'published'
);

$result = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $exporter = new SiteExporter(
            $pageRepository,
            new BlockRepository($pdo),
            APP_ROOT . '/export'
        );

        $result = $exporter->export();

        // Listen genindlæses, så tidsstemplerne i tabellen er de nye.
        $allPages = $pageRepository->findAll();

    } catch (Throwable $e) {
        error_log('Eksport fejlede: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Udgiv</title>
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
        <li><a href="create-page.php">Opret side</a></li>
        <li><a href="export.php" aria-current="page">Udgiv</a></li>
        <li><a href="#">Galleri</a></li>
        <li><a href="#">Indstillinger</a></li>
    </ul>
</nav>

<main class="content">
    <h1 class="content__title">Udgiv website</h1>

    <?php if ($error !== null): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($result !== null): ?>
        <div class="panel panel--success">
            <h2>Websitet er bygget</h2>
            <p>
                <?= (int) $result['pages'] ?> side<?= $result['pages'] === 1 ? '' : 'r' ?>
                og <?= (int) $result['assets'] ?> fil<?= $result['assets'] === 1 ? '' : 'er' ?>
                blev skrevet til <code>export/</code>.
            </p>

            <?php if ($result['warnings'] !== []): ?>
                <p><strong>Bemærkninger:</strong></p>
                <ul>
                    <?php foreach ($result['warnings'] as $warning): ?>
                        <li><?= e($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p>
                <a class="btn btn--primary" href="<?= e($basePath) ?>/export/index.html"
                   target="_blank" rel="noopener">Åbn det færdige website</a>
            </p>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2>Sådan virker det</h2>
        <p>
            Alle <strong>udgivne</strong> sider skrives som færdige HTML-filer i
            mappen <code>export/</code> sammen med den CSS og de billeder, de bruger.
            Kladder springes over.
        </p>
        <p>
            Den øverste udgivne side i <a href="index.php">Dine sider</a> bliver
            forsiden. Rækkefølgen ændrer du ved at trække i listen.
        </p>
        <p class="field__hint">
            Mappen tømmes hver gang, så filer fra en tidligere udgivelse
            ikke bliver hængende.
        </p>
    </div>

    <h2 class="section-heading">Sider der kommer med</h2>

    <table class="table">
        <thead>
            <tr>
                <th scope="col">Side</th>
                <th scope="col">Adresse</th>
                <th scope="col">Status</th>
                <th scope="col">Sidst udgivet</th>
            </tr>
        </thead>
        <tbody>
            <?php $isFirstPublished = true; ?>
            <?php foreach ($allPages as $page): ?>
                <?php
                    $isPublished = $page['status'] === 'published';
                    $isFront     = $isPublished && $isFirstPublished
                        && $page['parent_id'] === null;

                    if ($isFront) {
                        $isFirstPublished = false;
                    }
                ?>
                <tr class="<?= $isPublished ? '' : 'is-muted' ?>">
                    <td><?= e($page['title']) ?></td>
                    <td>
                        <code>
                            <?= $isFront ? '/' : '/' . e($page['slug']) . '/' ?>
                        </code>
                        <?= $isFront ? '<small>(forside)</small>' : '' ?>
                    </td>
                    <td>
                        <span class="badge badge--<?= e($page['status']) ?>">
                            <?= $isPublished ? 'udgivet' : 'kladde' ?>
                        </span>
                    </td>
                    <td>
                        <?= $page['last_published_at'] !== null
                            ? e(date('d/m/Y H:i', strtotime((string) $page['last_published_at'])))
                            : '<span class="is-muted">aldrig</span>' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($published === []): ?>
        <p class="alert" role="alert">
            Ingen sider er udgivet endnu. Åbn en side i editoren og sæt status til
            "Udgivet", før du bygger websitet.
        </p>
    <?php endif; ?>

    <form method="post" class="actions">
        <button type="submit" class="btn btn--primary"
                <?= $published === [] ? 'disabled' : '' ?>>
            Byg website
        </button>
    </form>
</main>

</body>
</html>