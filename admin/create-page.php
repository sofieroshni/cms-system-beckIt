<?php
require_once '../include/database.php';
// include '../include/database.php';
// Håndter formular-indsendelse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']);
    $slug       = trim($_POST['slug']);
    $templateId = (int)$_POST['template_id'];

    // 1. Opret selve siden
    $stmt = $connection->prepare(
        "INSERT INTO pages (title, slug, status, sort_order) VALUES (?, ?, 'draft', 0)"
    );
    $stmt->bind_param('ss', $title, $slug);
    $stmt->execute();
    $newPageId = $connection->insert_id; // ID på den lige oprettede side

    // 2. Hvis en skabelon er valgt (ikke "Blank"), kopiér dens blokke ind
    if ($templateId > 0) {
        $stmt = $connection->prepare(
            "SELECT block_type, sort_order, default_settings FROM template_blocks WHERE template_id = ?"
        );
        $stmt->bind_param('i', $templateId);
        $stmt->execute();
        $templateBlocks = $stmt->get_result();

        $insert = $connection->prepare(
            "INSERT INTO page_blocks (page_id, block_type, sort_order, settings) VALUES (?, ?, ?, ?)"
        );
        while ($block = $templateBlocks->fetch_assoc()) {
            $insert->bind_param(
                'isis',
                $newPageId,
                $block['block_type'],
                $block['sort_order'],
                $block['default_settings']
            );
            $insert->execute();
        }
    }

    // 3. Send brugeren videre til editoren for den nye side
    header('Location: editor.php?page_id=' . $newPageId);
    exit;
}

// Hent alle tilgængelige skabeloner til dropdown-menuen
$templates = mysqli_query($connection, "SELECT * FROM page_templates ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Opret side</title>
</head>
<body>
    <?php include('../blocks/Navbar.php'); ?>

    <h1>Opret ny side</h1>
    <form method="POST">
        <label>Titel: <input type="text" name="title" required></label><br>
        <label>Slug: <input type="text" name="slug" required></label><br>

        <label>Skabelon:
            <select name="template_id">
                <option value="0">Blank (tom side)</option>
                <?php while ($t = mysqli_fetch_assoc($templates)): ?>
                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </label><br>

        <button type="submit">Opret side</button>
    </form>
</body>
</html>