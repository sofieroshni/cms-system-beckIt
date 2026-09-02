<?php
declare(strict_types=1);

/**
 * Gemmer hele siden i ét kald.
 *
 * Modtager JSON fra editoren og svarer med JSON. Ingen videresendelse,
 * ingen HTML — browseren bliver på siden.
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

$pageId = filter_input(INPUT_GET, 'page_id', FILTER_VALIDATE_INT) ?: 0;

// Data kommer som en JSON-body, ikke som formularfelter, så $_POST er tom.
$raw     = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    respond(400, ['ok' => false, 'error' => 'Ugyldigt dataformat.']);
}

$pdo = Database::getConnection();

$saver = new PageSaver(
    $pdo,
    new PageRepository($pdo),
    new BlockRepository($pdo)
);

try {
    $result = $saver->save($pageId, $payload);

    // Nye blokke fik tildelt id'er undervejs. De sendes tilbage i samme
    // rækkefølge, som blokkene blev gemt, så editoren kan sætte dem på
    // sine elementer. Uden det ville næste gemning oprette blokkene igen.
    $ids = array_map(
        static fn (array $block): int => (int) $block['id'],
        (new BlockRepository($pdo))->findByPage($pageId)
    );

    respond(200, [
        'ok'      => true,
        'ids'     => $ids,
        'blocks'  => $result['blocks'],
        'deleted' => $result['deleted'],
    ]);

} catch (InvalidArgumentException $e) {
    // Fejl brugeren selv kan rette — vis beskeden.
    respond(422, ['ok' => false, 'error' => $e->getMessage()]);

} catch (Throwable $e) {
    // Tekniske fejl logges, men detaljerne sendes ikke til browseren.
    error_log('Gemning af side ' . $pageId . ' fejlede: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'Der opstod en teknisk fejl.']);
}