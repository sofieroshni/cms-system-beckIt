<?php
declare(strict_types=1);

/**
 * Modtager formularen fra create-page.php og opretter siden.
 *
 * Filen sender ingen HTML. Den arbejder og videresender — mønsteret
 * Post/Redirect/Get. Uden det ville et tryk på F5 efter oprettelsen
 * sende formularen igen og lave en side mere.
 *
 * BEMÆRK OM ADGANGSKONTROL
 * Der er hverken login eller CSRF-beskyttelse her. Det er en bevidst
 * beslutning: CMS'et kører udelukkende på localhost, og det færdige
 * website udgives som statiske filer via FTP. Der er altså ingen
 * offentligt tilgængelig PHP at angribe.
 * Skal admin nogensinde ligge på en rigtig server, SKAL både login og
 * CSRF-tokens på plads, før det sker.
 */

require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-page.php');
    exit;
}

$title      = (string) ($_POST['title'] ?? '');
$templateId = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT) ?: 0;

$pdo = Database::getConnection();

$builder = new PageBuilder(
    $pdo,
    new PageRepository($pdo),
    new BlockRepository($pdo),
    new TemplateRepository($pdo)
);

try {
    // 0 er den blanke side. Alt andet slås op som skabelon-id, og
    // findes det ikke, kaster PageBuilder en fejl.
    $pageId = $templateId > 0
        ? $builder->createFromTemplate($templateId, $title)
        : $builder->createBlank($title);

    header('Location: editor.php?page_id=' . $pageId);
    exit;

} catch (InvalidArgumentException $e) {
    // Fejl brugeren selv kan rette — vis beskeden.
    header('Location: create-page.php?fejl=' . urlencode($e->getMessage()));
    exit;

} catch (Throwable $e) {
    // Alt andet er en teknisk fejl. Detaljerne logges, men vises ikke:
    // en databasefejl kan indeholde tabelnavne og forespørgsler.
    error_log('Oprettelse af side fejlede: ' . $e->getMessage());

    header('Location: create-page.php?fejl='
        . urlencode('Siden kunne ikke oprettes. Prøv igen.'));
    exit;
}