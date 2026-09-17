<?php
declare(strict_types=1);

/**
 * Skifter en sides status mellem kladde og udgivet.
 *
 * Kaldes fra oversigten og gemmer med det samme. Det er en enkelt,
 * afgraenset handling — ligesom omrokeringen af sider — og en gem-knap
 * til netop den ville vaere unoedigt bureaukrati.
 *
 * Adgangskontrol er bevidst udeladt; se begrundelsen i store-page.php.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Kun POST er tilladt.']));
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Ugyldigt dataformat.']));
}

$pageId     = (int) ($payload['id'] ?? 0);
$repository = new PageRepository(Database::getConnection());
$page       = $repository->find($pageId);

if ($page === null) {
    http_response_code(404);
    exit(json_encode(['ok' => false, 'error' => 'Siden findes ikke.']));
}

// Den nye status udledes af den nuvaerende i databasen, ikke af det
// browseren sender. Ellers kunne to faner, der begge stod aabne, skrive
// hver sin opfattelse af tilstanden ned oven i hinanden.
$newStatus = $page['status'] === 'published' ? 'draft' : 'published';

try {
    $repository->setStatus($pageId, $newStatus);

    echo json_encode([
        'ok'     => true,
        'status' => $newStatus,
        'label'  => $newStatus === 'published' ? 'udgivet' : 'kladde',
    ]);

} catch (Throwable $e) {
    error_log('Statusskift for side ' . $pageId . ' fejlede: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Status kunne ikke aendres.']);
}