<?php
require_once '../include/database.php';
require_once '../core/BlockRegistry.php';

// Kun POST-requests må gemme data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ugyldig forespørgsel.');
}

$blockId = (int)$_POST['block_id'];
$pageId  = (int)$_POST['page_id'];

// Hent blokken, så vi ved hvilken type den er
$stmt = $connection->prepare("SELECT block_type FROM page_blocks WHERE id = ?");
$stmt->bind_param('i', $blockId);
$stmt->execute();
$block = $stmt->get_result()->fetch_assoc();

if (!$block) {
    die('Blokken blev ikke fundet.');
}

// Slå klassen op og hent dens skema
$className = BlockRegistry::get($block['block_type']);
if (!$className) {
    die('Ukendt bloktype.');
}
$schema = $className::getSchema();

// Byg settings-array ud fra KUN de felter, skemaet tillader
$settings = [];
foreach ($schema as $fieldName => $fieldConfig) {
    $settings[$fieldName] = $_POST[$fieldName] ?? '';
}

// Gem som JSON i databasen
$json = json_encode($settings);
$stmt = $connection->prepare("UPDATE page_blocks SET settings = ? WHERE id = ?");
$stmt->bind_param('si', $json, $blockId);
$stmt->execute();

// Send brugeren tilbage til editoren
header('Location: editor.php?page_id=' . $pageId);
exit;