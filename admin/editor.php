<?php
require_once '../include/database.php';
require_once '../core/BlockRegistry.php';

$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

$stmt = $connection->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->bind_param('i', $pageId);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    die('Siden blev ikke fundet.');
}

$stmt = $connection->prepare("SELECT * FROM page_blocks WHERE page_id = ? ORDER BY sort_order ASC");
$stmt->bind_param('i', $pageId);
$stmt->execute();
$blocks = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Rediger: <?= htmlspecialchars($page['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <!-- ====== Sideindstillinger (egen form, uafhængig af blokkene) ====== -->
    <form method="POST" action="save-page.php" class="page-settings">
        <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

        <label>Titel:
            <input type="text" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>
        </label>

        <label>Slug:
            <input type="text" name="slug" value="<?= htmlspecialchars($page['slug']) ?>" required>
        </label>

        <label>Status:
            <select name="status">
                <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Kladde</option>
                <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Udgivet</option>
            </select>
        </label>

        <button type="submit">Gem sideindstillinger</button>
    </form>
    <hr>

    <h1>Redigerer: <?= htmlspecialchars($page['title']) ?></h1>

    <!-- ====== Alle blokke i ÉN form — dette er "gem alt" ====== -->
    <form method="POST" action="save-all-blocks.php" id="all-blocks-form">
        <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

        <div class="editor-sections">
            <?php while ($block = mysqli_fetch_assoc($blocks)): ?>
                <?php
                    $className = BlockRegistry::get($block['block_type']);
                    $data      = json_decode($block['settings'], true) ?? [];
                    $schema    = $className ? $className::getSchema() : [];
                    $blockId   = (int)$block['id'];
                ?>
                <div class="editor-section" data-block-id="<?= $blockId ?>">
                    <span class="section-label"><?= htmlspecialchars($block['block_type']) ?></span>

                    <!-- Slet-knap: et LINK, ikke en form — så den kan ligge inde i all-blocks-form uden nested <form> -->
                    <a href="delete-block.php?block_id=<?= $blockId ?>&page_id=<?= (int)$page['id'] ?>"
                       class="delete-button"
                       onclick="return confirm('Slet denne blok?')">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>

                    <div class="section-preview">
                        <?= $className ? $className::render($data) : '(ukendt blok-type)' ?>
                    </div>

                    <?php foreach ($schema as $fieldName => $fieldConfig): ?>
                        <label>
                            <?= htmlspecialchars($fieldConfig['label']) ?>:
                            <?php if ($fieldConfig['type'] === 'richtext'): ?>
                                <textarea name="blocks[<?= $blockId ?>][<?= htmlspecialchars($fieldName) ?>]"><?= htmlspecialchars($data[$fieldName] ?? '') ?></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="blocks[<?= $blockId ?>][<?= htmlspecialchars($fieldName) ?>]"
                                       value="<?= htmlspecialchars($data[$fieldName] ?? '') ?>">
                            <?php endif; ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
                <hr>
            <?php endwhile; ?>
        </div>
    </form>
    <!-- ====== all-blocks-form slutter her — resten er UDENFOR den ====== -->

    <!-- Tilføj ny blok (egen form, ligger nu KUN én gang, uden for while-loopet) -->
    <div class="add-block">
        <form method="POST" action="add-block.php">
            <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

            <select name="block_type">
                <?php foreach (BlockRegistry::all() as $type => $class): ?>
                    <option value="<?= htmlspecialchars($type) ?>">
                        <?= htmlspecialchars(ucfirst($type)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">+ Tilføj sektion</button>
        </form>
    </div>

    <footer>
        <div class="footer-buttons">
            <a class="btn" href="preview.php?page_id=<?= (int)$page['id'] ?>">
                <button type="button" class="btn eye"><i class="fa-solid fa-eye"></i> Show</button>
            </a>
            <button type="button" class="btn cta">Publish</button>

            <!-- Denne knap submitter all-blocks-form selvom den ligger uden for den -->
            <button type="submit" form="all-blocks-form" class="btn">Gem</button>
        </div>
    </footer>
</body>
</html>
<style>
    body {
        font-family: 'Jost', sans-serif;
    }
    a {
        text-decoration: none;
    }
    button {
        background-color: blue;
        color: white;
        border-radius: 5px;
        padding: 10px;
        margin: 10px;
        border: none;
    }
    button.cta {
        background-color: green;
        color: white;
        border-radius: 5px;
        padding: 10px;
        margin: 10px;
        border: none;
    }
    button.eye {
        display: flex;
        align-items: center;
    }
    button.eye i {
        margin-right: 10px;
    }
    .delete-button {
        background: none;
        border: none;
        color: red;
        cursor: pointer;
        z-index: 10;
        font-size: 20px;
        position: absolute;
        margin-top: -10px;
        margin-left: 10px;
    }
    .fa-circle-xmark {
        background-color: transparent;
    }
    footer {
        display: flex;
        justify-content: end;
        margin-top: 20px;
        height: 100px;
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: red;
    }
    .footer-buttons {
        display: flex;
        justify-content: end;
        align-items: center;
        width: 50%;
        background-color: lightblue;
    }
</style>