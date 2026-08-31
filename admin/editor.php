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
</head>
<body>

    <h1>Redigerer: <?= htmlspecialchars($page['title']) ?></h1>

    <div class="editor-sections">
        <?php while ($block = mysqli_fetch_assoc($blocks)): ?>
            <div class="editor-section" data-block-id="<?= (int)$block['id'] ?>">
                <span class="section-label"><?= htmlspecialchars($block['block_type']) ?></span>
                <button class="edit-btn">✎</button>
                <button class="delete-btn">✕</button>
                <div class="section-preview">
                    <?php
                        $className = BlockRegistry::get($block['block_type']);
                        $data = json_decode($block['settings'], true) ?? [];
                        echo $className ? $className::render($data) : '(ukendt blok-type)';
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>