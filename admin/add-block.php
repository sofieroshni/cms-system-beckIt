<?php
require_once '../include/database.php';
require_once '../core/BlockRegistry.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ugyldig forespørgsel.');
}

$pageId    = (int)$_POST['page_id'];
$blockType = $_POST['block_type'] ?? '';

// Tjek at bloktypen faktisk findes i registry'et
$className = BlockRegistry::get($blockType);
if (!$className) {
    die('Ukendt bloktype.');
}

// Find den højeste sort_order på siden, så den nye blok lægges nederst
$stmt = $connection->prepare(
    "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM page_blocks WHERE page_id = ?"
);
$stmt->bind_param('i', $pageId);
$stmt->execute();
$nextOrder = (int)$stmt->get_result()->fetch_assoc()['next_order'];

// Byg tomme standardværdier ud fra blokkens skema
$settings = [];
foreach ($className::getSchema() as $fieldName => $fieldConfig) {
    $settings[$fieldName] = '';
}
$json = json_encode($settings);

// Indsæt den nye blok
$stmt = $connection->prepare(
    "INSERT INTO page_blocks (page_id, block_type, sort_order, settings) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('isis', $pageId, $blockType, $nextOrder, $json);
$stmt->execute();

header('Location: editor.php?page_id=' . $pageId);
exit;