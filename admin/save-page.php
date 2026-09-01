<?php
require_once '../include/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ugyldig forespørgsel.');
}

$pageId = (int)$_POST['page_id'];
$title  = trim($_POST['title'] ?? '');
$slug   = trim($_POST['slug'] ?? '');
$status = $_POST['status'] ?? 'draft';

if (!in_array($status, ['draft', 'published'], true)) {
    $status = 'draft';
  
}

$stmt = $connection->prepare(
    "UPDATE pages SET title = ?, slug = ?, status = ? WHERE id = ?"
);
$stmt->bind_param('sssi', $title, $slug, $status, $pageId);
$stmt->execute();

header('Location: editor.php?page_id=' . $pageId);
exit;