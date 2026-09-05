<?php
require_once '../include/database.php';

$blockId = isset($_GET['block_id']) ? (int)$_GET['block_id'] : 0;
$pageId  = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

if ($blockId) {
    $stmt = $connection->prepare("DELETE FROM page_blocks WHERE id = ? AND page_id = ?");
    $stmt->bind_param('ii', $blockId, $pageId);
    $stmt->execute();
}

header("Location: editor.php?page_id=" . $pageId);
exit;