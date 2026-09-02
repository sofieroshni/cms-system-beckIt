<?php
declare(strict_types=1);

/**
 * Gemmer sidernes rækkefølge efter træk-og-slip.
 *
 * Til forskel fra editoren gemmes her med det samme. Det er en enkelt,
 * afgrænset handling, og en gem-knap til netop den ville føles som
 * unødigt bureaukrati.
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
$ids     = $payload['ids'] ?? null;

if (!is_array($ids) || $ids === []) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Ingen rækkefølge modtaget.']));
}

// Kun heltal slipper igennem. Ukendte id'er rammer ingenting, fordi
// UPDATE'en har dem i sin WHERE.
$ids = array_values(array_filter(array_map('intval', $ids)));

try {
    (new PageRepository(Database::getConnection()))->reorder($ids);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    error_log('Omrokering af sider fejlede: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Rækkefølgen kunne ikke gemmes.']);
}