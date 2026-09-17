<?php
declare(strict_types=1);

/**
 * Gemmer én delt blok.
 *
 * Modtager JSON og svarer med JSON, ligesom save-page.php. Der er ingen
 * transaktion her, fordi der kun skrives til én raekke.
 *
 * Adgangskontrol er bevidst udeladt; se begrundelsen i store-page.php.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $data
 */
function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Kun POST er tilladt.']);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($payload)) {
    respond(400, ['ok' => false, 'error' => 'Ugyldigt dataformat.']);
}

$id         = (int) ($payload['id'] ?? 0);
$repository = new SharedBlockRepository(Database::getConnection());
$block      = $repository->find($id);

if ($block === null) {
    respond(404, ['ok' => false, 'error' => 'Den delte blok findes ikke.']);
}

// Bloktypen laeses fra databasen, aldrig fra det browseren sender.
// Ellers kunne et andet skema bruges til at gemme felter, blokken ikke har.
$class = BlockRegistry::get((string) $block['block_type']);

if ($class === null) {
    respond(422, ['ok' => false, 'error' => 'Ukendt bloktype.']);
}

try {
    $repository->update(
        $id,
        (string) ($payload['name'] ?? $block['name']),
        FieldValidator::validateAll(
            $class::getSchema(),
            is_array($payload['settings'] ?? null) ? $payload['settings'] : []
        ),
        FieldValidator::validateAll(
            $class::getStyleSchema(),
            is_array($payload['styles'] ?? null) ? $payload['styles'] : []
        )
    );

    respond(200, ['ok' => true]);

} catch (InvalidArgumentException $e) {
    respond(422, ['ok' => false, 'error' => $e->getMessage()]);

} catch (Throwable $e) {
    error_log('Gemning af delt blok ' . $id . ' fejlede: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'Der opstod en teknisk fejl.']);
}