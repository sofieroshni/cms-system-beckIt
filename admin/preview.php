<?php
require_once '../include/database.php';
require_once '../core/BlockRegistry.php';
    // require_once '/components/FooterButtons.php';

$pageId = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

// Hent siden
$stmt = $connection->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->bind_param('i', $pageId);
$stmt->execute();

$page = $stmt->get_result()->fetch_assoc();

if (!$page) {
    die('Siden blev ikke fundet.');
}

// Hent blocks
$stmt = $connection->prepare(
    "SELECT * FROM page_blocks WHERE page_id = ? ORDER BY sort_order ASC"
);

$stmt->bind_param('i', $pageId);
$stmt->execute();

$blocks = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="da">

<head>
    <meta charset="UTF-8">

    <title>
        <?= htmlspecialchars($page['title']) ?>
    </title>
</head>

<body>

    <?php while ($block = mysqli_fetch_assoc($blocks)): ?>

        <?php
            $className = BlockRegistry::get($block['block_type']);

            $data = json_decode(
                $block['settings'],
                true
            ) ?? [];
        ?>

        <?php if ($className): ?>

            <?= $className::render($data) ?>

        <?php endif; ?>

    <?php endwhile; ?>

</body>

</html>
