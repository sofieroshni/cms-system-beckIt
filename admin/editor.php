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
    
    <!-- Sidens indstillinger -->
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

    <div class="editor-sections">
        <?php while ($block = mysqli_fetch_assoc($blocks)): ?>
            <?php
                $className = BlockRegistry::get($block['block_type']);
                $data      = json_decode($block['settings'], true) ?? [];
                $schema    = $className ? $className::getSchema() : [];
            ?>
            <div class="editor-section" data-block-id="<?= (int)$block['id'] ?>">
                <span class="section-label"><?= htmlspecialchars($block['block_type']) ?></span>

                    <form method="POST" action="delete-block.php" class="delete-form"
                 >
                    <input type="hidden" name="block_id" value="<?= (int)$block['id'] ?>">
                    <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">
                    <button type="submit" class="delete-button">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </form>

                <div class="section-preview">
                    <?= $className ? $className::render($data) : '(ukendt blok-type)' ?>
                </div>

                <form method="POST" action="save-block.php" class="block-form">
                    <input type="hidden" name="block_id" value="<?= (int)$block['id'] ?>">
                    <input type="hidden" name="page_id" value="<?= (int)$page['id'] ?>">

                    <?php foreach ($schema as $fieldName => $fieldConfig): ?>
                        <label>
                            <?= htmlspecialchars($fieldConfig['label']) ?>:
                            <?php if ($fieldConfig['type'] === 'richtext'): ?>
                                <textarea name="<?= htmlspecialchars($fieldName) ?>"><?= htmlspecialchars($data[$fieldName] ?? '') ?></textarea>
                            <?php else: ?>
                                <input type="text"
                                       name="<?= htmlspecialchars($fieldName) ?>"
                                       value="<?= htmlspecialchars($data[$fieldName] ?? '') ?>">
                            <?php endif; ?>
                        </label><br>
                    <?php endforeach; ?>

                    <button type="submit">Gem</button>
                </form>
            </div>
            <hr>
        <?php endwhile; ?>
    </div>

    <!-- Tilføj ny blok -->
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
   <button class="cta-btn"><a href="index.php">tilbage til dine sider index.php</a><button>
</body>
</html>
<style>
    .delete-button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        z-index: 10;
        color:red;
        font-size: 20px;
    }
    .fa-circle-xmark{
        background-color:transparent;

        
    }
</style>