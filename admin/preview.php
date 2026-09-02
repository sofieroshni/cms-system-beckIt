<?php
declare(strict_types=1);

/**
 * Forhåndsvisning af ugemt arbejde.
 *
 * Editoren sender sidens nuværende tilstand med, og siden tegnes præcis
 * som den ville se ud på det færdige website. Der skrives intet til
 * databasen.
 *
 * Det er muligt, fordi PageRenderer ikke selv henter data. Den tager en
 * liste af blokke ind — og er ligeglad med, om listen kom fra databasen
 * eller fra en formular, brugeren aldrig har gemt.
 */

require_once __DIR__ . '/../bootstrap.php';

$pageId = filter_input(INPUT_GET, 'page_id', FILTER_VALIDATE_INT) ?: 0;

$pdo  = Database::getConnection();
$page = (new PageRepository($pdo))->find($pageId);

if ($page === null) {
    http_response_code(404);
    exit('Siden blev ikke fundet.');
}

$state = json_decode((string) ($_POST['state'] ?? ''), true);

if (!is_array($state)) {
    http_response_code(400);
    exit('Ingen data at forhåndsvise.');
}

// Titlen kan være ændret i editoren uden at være gemt endnu.
if (isset($state['page']['title'])) {
    $page['title'] = (string) $state['page']['title'];
}

// Editorens format oversættes til det, rendereren forventer. Værdierne
// valideres på samme måde som ved gemning, så forhåndsvisningen viser
// nøjagtigt det, der ville blive gemt — inklusive de rettelser
// valideringen laver undervejs.
$blocks = [];

foreach ((array) ($state['blocks'] ?? []) as $incoming) {
    $type  = (string) ($incoming['type'] ?? '');
    $class = BlockRegistry::get($type);

    if ($class === null) {
        continue;
    }

    $blocks[] = [
        'block_type' => $type,
        'settings'   => FieldValidator::validateAll(
            $class::getSchema(),
            is_array($incoming['settings'] ?? null) ? $incoming['settings'] : []
        ),
        'styles'     => FieldValidator::validateAll(
            $class::getStyleSchema(),
            is_array($incoming['styles'] ?? null) ? $incoming['styles'] : []
        ),
    ];
}

$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

header('Content-Type: text/html; charset=utf-8');

echo PageRenderer::renderDocument(
    $page,
    $blocks,
    RenderContext::editor($basePath)
);