<?php
require_once '../include/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ugyldig forespørgsel.');
}

$blockId = (int)$_POST['block_id'];
$pageId  = (int)$_POST['page_id'];

$stmt = $connection->prepare("DELETE FROM page_blocks WHERE id = ?");
$stmt->bind_param('i', $blockId);
$stmt->execute();

header('Location: editor.php?page_id=' . $pageId);
exit;