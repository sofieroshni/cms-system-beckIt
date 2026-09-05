<?php
require_once '../include/database.php';

$pageId = isset($_POST['page_id']) ? (int)$_POST['page_id'] : 0;
$blocksData = $_POST['blocks'] ?? [];

if (!$pageId || empty($blocksData)) {
    die('Ingen blokke at gemme.');
}

$stmt = $connection->prepare(
    "UPDATE page_blocks SET settings = ? WHERE id = ? AND page_id = ?"
);

foreach ($blocksData as $blockId => $fields) {
    $blockId = (int)$blockId;
    $settingsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('sii', $settingsJson, $blockId, $pageId);
    $stmt->execute();
}

header("Location: editor.php?page_id=" . $pageId);
exit;