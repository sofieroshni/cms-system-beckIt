<?php
declare(strict_types=1);

/**
 * Viser en side, som den vil se ud på det færdige website.
 *
 * Bruges under udvikling og som forhåndsvisning fra editoren. Ved eksport
 * i fase 7 kaldes præcis samme renderer, blot med en export-kontekst, og
 * resultatet skrives til en .html-fil i stedet for at blive sendt til
 * browseren. Der er altså kun én renderingsvej at vedligeholde.
 *
 *   page.php?id=1
 */

require_once __DIR__ . '/bootstrap.php';

$pageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$pdo    = Database::getConnection();
$pages  = new PageRepository($pdo);
$blocks = new BlockRepository($pdo);

$page = $pages->find($pageId);

if ($page === null) {
    http_response_code(404);
    exit('Siden blev ikke fundet.');
}

// Kun synlige blokke ryger med ud på den offentlige side.
// Editoren viser også de skjulte, så de kan slås til igen.
$pageBlocks = $blocks->findByPage($pageId, onlyVisible: true);

// Basisstien svarer til den mappe, projektet ligger i under htdocs.
// Ved eksport erstattes den af RenderContext::export().
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$context  = RenderContext::editor($basePath);

header('Content-Type: text/html; charset=utf-8');

echo PageRenderer::renderDocument($page, $pageBlocks, $context);