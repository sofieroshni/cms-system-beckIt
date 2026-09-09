<?php
declare(strict_types=1);

/**
 * Modtager et billede fra editoren og svarer med stien.
 *
 * Filen gemmes med det samme — også selvom brugeren aldrig trykker Gem på
 * siden. Det er et bevidst valg: alternativet ville være at holde filen i
 * en midlertidig tilstand, indtil siden blev gemt, og det ville kræve
 * oprydning af filer, ingen nogensinde tog i brug.
 *
 * Resultatet er, at der kan samle sig ubrugte billeder i uploads/. Det
 * hører til et mediebibliotek, hvor de kan ses og slettes, frem for til
 * logik her.
 *
 * Adgangskontrol er bevidst udeladt; se begrundelsen i store-page.php.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $data
 */
function reply(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'Kun POST er tilladt.']);
}

if (!isset($_FILES['image'])) {
    reply(400, ['ok' => false, 'error' => 'Ingen fil modtaget.']);
}

try {
    $uploader = new ImageUploader(APP_ROOT . '/uploads');
    $path     = $uploader->store($_FILES['image']);

    reply(200, ['ok' => true, 'path' => $path]);

} catch (RuntimeException $e) {
    // Beskederne fra ImageUploader er skrevet til brugeren og kan vises.
    reply(422, ['ok' => false, 'error' => $e->getMessage()]);

} catch (Throwable $e) {
    error_log('Billedupload fejlede: ' . $e->getMessage());
    reply(500, ['ok' => false, 'error' => 'Der opstod en teknisk fejl.']);
}