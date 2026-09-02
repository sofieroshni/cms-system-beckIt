<?php
declare(strict_types=1);

/**
 * Sletter en side.
 *
 * Sidens blokke forsvinder automatisk via ON DELETE CASCADE i databasen.
 * Har siden undersider, nægter databasen at slette den (ON DELETE
 * RESTRICT), og brugeren får en forklaring frem for en rå SQL-fejl.
 */

require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$pageId = filter_input(INPUT_POST, 'page_id', FILTER_VALIDATE_INT) ?: 0;
$pages  = new PageRepository(Database::getConnection());

try {
    $pages->delete($pageId);
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // 23000 dækker brud på integritetsregler. Her kan det kun være
    // fremmednøglen fra undersider.
    $message = $e->getCode() === '23000'
        ? 'Siden har undersider og kan ikke slettes. Slet eller flyt dem først.'
        : 'Siden kunne ikke slettes.';

    error_log('Sletning af side ' . $pageId . ' fejlede: ' . $e->getMessage());

    header('Location: index.php?fejl=' . urlencode($message));
    exit;
}