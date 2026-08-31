<?php
require_once '../include/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ugyldig forespørgsel.');
}

$pageId = (int)$_POST['page_id'];

$stmt = $connection->prepare("DELETE FROM pages WHERE id = ?");
$stmt->bind_param('i', $pageId);
$stmt->execute();

header('Location: index.php');
exit;