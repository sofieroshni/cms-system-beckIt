<?php
declare(strict_types=1);

/**
 * Editoren.
 *
 * Sidens tilstand holdes i browseren. Serveren leverer udgangspunktet og
 * de formularfelter, hver bloktype har brug for; JavaScript holder styr
 * på hvad der er ændret og sender det hele samlet, når brugeren gemmer.
 *
 * Formularfelterne bygges her i PHP ud fra blokkenes skemaer — ikke i
 * JavaScript. Skemaet er sandheden om, hvilke felter der findes, og den
 * viden skal kun ligge ét sted.
 */

require_once __DIR__ . '/../bootstrap.php';

$pageId = filter_input(INPUT_GET, 'page_id', FILTER_VALIDATE_INT) ?: 0;

$pdo             = Database::getConnection();
$pageRepository  = new PageRepository($pdo);
$blockRepository = new BlockRepository($pdo);

$page = $pageRepository->find($pageId);

if ($page === null) {
    http_response_code(404);
    exit('Siden blev ikke fundet.');
}

$blocks   = $blockRepository->findByPage($pageId);
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$context  = RenderContext::editor($basePath);

/**
 * Tegner ét formularfelt ud fra dets skemadefinition.
 *
 * @param array<string, mixed> $field
 */
function renderField(string $scope, string $name, array $field, mixed $value): string
{
    $id    = 'f_' . $scope . '_' . $name . '_' . bin2hex(random_bytes(3));
    $type  = $field['type'] ?? 'text';
    $label = (string) ($field['label'] ?? $name);

    $attributes = 'id="' . e($id) . '"'
        . ' data-scope="' . e($scope) . '"'
        . ' data-field="' . e($name) . '"';

    $input = match ($type) {
        'textarea' => '<textarea ' . $attributes . ' rows="4">'
            . e((string) $value) . '</textarea>',

        'color' => '<input type="color" ' . $attributes
            . ' value="' . e($value !== '' ? (string) $value : '#000000') . '">',

        'number' => '<input type="number" ' . $attributes
            . ' value="' . e((string) $value) . '"'
            . ' min="' . (int) ($field['min'] ?? 0) . '"'
            . ' max="' . (int) ($field['max'] ?? 9999) . '">',

        'select' => (static function () use ($attributes, $field, $value): string {
            $html = '<select ' . $attributes . '>';
            foreach ($field['options'] ?? [] as $option) {
                $html .= '<option value="' . e($option) . '"'
                    . ((string) $value === (string) $option ? ' selected' : '')
                    . '>' . e($option) . '</option>';
            }
            return $html . '</select>';
        })(),

        default => '<input type="text" ' . $attributes
            . ' value="' . e((string) $value) . '">',
    };

    return '<p class="ed-field">'
        . '<label for="' . e($id) . '">' . e($label) . '</label>'
        . $input
        . '</p>';
}

/**
 * Tegner et repeater-felt: et vilkårligt antal ens rækker.
 *
 * Bruges til punktlister, navigationslinks og lignende. Rækkerne ligger i
 * blokkens egen JSON, ikke som selvstændige blokke i databasen — det er
 * dét, der sparer os for indlejrede blokke med parent_id, og dermed for
 * rekursiv rendering og forældreløse rækker ved sletning.
 *
 * En tom <template> nederst fungerer som skabelon, når brugeren tilføjer
 * en række. Så bygger JavaScript ikke felter selv; det kloner bare det,
 * PHP allerede har tegnet ud fra skemaet.
 *
 * @param array<string, mixed>                $field
 * @param array<int, array<string, mixed>>    $rows
 */
function renderRepeater(string $name, array $field, array $rows): string
{
    $subSchema = $field['fields'] ?? [];

    $renderRow = static function (array $row) use ($subSchema): string {
        $html = '<div class="ed-row">';

        foreach ($subSchema as $subName => $subField) {
            $type  = $subField['type'] ?? 'text';
            $value = e((string) ($row[$subName] ?? ''));
            $attrs = 'data-rfield="' . e($subName) . '"'
                . ' aria-label="' . e((string) ($subField['label'] ?? $subName)) . '"';

            $html .= $type === 'textarea'
                ? '<textarea ' . $attrs . ' rows="2">' . $value . '</textarea>'
                : '<input type="text" ' . $attrs . ' value="' . $value . '">';
        }

        return $html
            . '<button type="button" class="ed-btn ed-btn--delete"'
            . ' data-action="remove-row" aria-label="Fjern række">&times;</button>'
            . '</div>';
    };

    $html = '<div class="ed-repeater" data-repeater="' . e($name) . '">'
        . '<span class="ed-repeater__label">'
        . e((string) ($field['label'] ?? $name)) . '</span>'
        . '<div class="ed-repeater__rows">';

    foreach ($rows as $row) {
        $html .= $renderRow(is_array($row) ? $row : []);
    }

    $html .= '</div>'
        . '<button type="button" class="ed-repeater__add" data-action="add-row">'
        . '+ Tilføj række</button>'
        . '<template data-row-template>' . $renderRow([]) . '</template>'
        . '</div>';

    return $html;
}

/**
 * Tegner hele redigeringspanelet for én blok.
 *
 * @param class-string<BlockInterface> $class
 * @param array<string, mixed>         $settings
 * @param array<string, mixed>         $styles
 */
function renderPanel(string $class, array $settings, array $styles): string
{
    $html = '<div class="ed-panel" hidden>';

    $html .= '<fieldset class="ed-group"><legend>Indhold</legend>';
    foreach ($class::getSchema() as $name => $field) {
        $value = $settings[$name] ?? '';

        $html .= ($field['type'] ?? '') === 'repeater'
            ? renderRepeater($name, $field, is_array($value) ? $value : [])
            : renderField('settings', $name, $field, $value);
    }
    $html .= '</fieldset>';

    $styleSchema = $class::getStyleSchema();

    if ($styleSchema !== []) {
        $html .= '<fieldset class="ed-group"><legend>Udseende</legend>';
        foreach ($styleSchema as $name => $field) {
            $html .= renderField('styles', $name, $field, $styles[$name] ?? '');
        }
        $html .= '</fieldset>';
    }

    return $html . '</div>';
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rediger: <?= e($page['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="editor.css">

    <?php /*
        CSS for ALLE bloktyper — ikke kun dem, der ligger på siden nu.
        Brugeren kan tilføje en hvilken som helst blok uden at genindlæse,
        og dens styling skal være på plads i det øjeblik den dukker op.
    */ ?>
    <?php foreach (PageRenderer::allStylesheets() as $sheet): ?>
        <link rel="stylesheet" href="<?= e($basePath . '/' . $sheet) ?>">
    <?php endforeach; ?>
</head>
<body class="editor" data-page-id="<?= (int) $page['id'] ?>">

<header class="ed-top">
    <a class="ed-back" href="index.php" aria-label="Tilbage til dine sider">&larr;</a>
    <h1 class="ed-title"><?= e($page['title']) ?></h1>
</header>

<section class="ed-settings">
    <p class="ed-field">
        <label for="page-title">Titel</label>
        <input type="text" id="page-title" data-page-field="title"
               value="<?= e($page['title']) ?>" maxlength="255">
    </p>
    <p class="ed-field">
        <label for="page-slug">Webadresse</label>
        <input type="text" id="page-slug" data-page-field="slug"
               value="<?= e($page['slug']) ?>" maxlength="255">
    </p>
    <p class="ed-field">
        <label for="page-status">Status</label>
        <select id="page-status" data-page-field="status">
            <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Kladde</option>
            <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Udgivet</option>
        </select>
    </p>
</section>

<main class="ed-canvas" id="canvas">
    <?php foreach ($blocks as $block): ?>
        <?php
            $class = BlockRegistry::get((string) $block['block_type']);
            if ($class === null) {
                continue;
            }
            $settings = FieldValidator::validateAll($class::getSchema(), $block['settings']);
            $styles   = FieldValidator::validateAll($class::getStyleSchema(), $block['styles']);
        ?>
        <article class="ed-block"
                 data-block-id="<?= (int) $block['id'] ?>"
                 data-block-type="<?= e($block['block_type']) ?>">

            <span class="ed-block__label"><?= e($class::label()) ?></span>

            <div class="ed-block__actions">
                <button type="button" class="ed-btn ed-btn--edit" data-action="edit"
                        aria-expanded="false">&#9998;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="up">&and;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="down">&or;</button>
                <button type="button" class="ed-btn ed-btn--delete" data-action="delete">&times;</button>
            </div>

            <div class="ed-block__preview">
                <?= $class::render($settings, $styles, $context) ?>
            </div>

            <?= renderPanel($class, $settings, $styles) ?>
        </article>
    <?php endforeach; ?>
</main>

<section class="ed-add">
    <button type="button" class="ed-add__toggle" id="add-toggle" aria-expanded="false">+</button>

    <div class="ed-add__menu" id="add-menu" hidden>
        <?php foreach (BlockRegistry::all() as $type => $label): ?>
            <button type="button" class="ed-add__choice" data-add-type="<?= e($type) ?>">
                <?= e($label) ?>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<footer class="ed-footer">
    <span class="ed-status" id="save-status" role="status" aria-live="polite"></span>
    <button type="button" class="btn btn--ghost" id="preview-btn">Forhåndsvis</button>
    <button type="button" class="btn btn--primary" id="save-btn" disabled>Gem</button>
</footer>

<?php
/*
 * Skabeloner til nye blokke.
 *
 * Hver bloktype ligger klar som en <template> med sit forhåndsvisning og
 * sine felter, udfyldt med standardværdier. Når brugeren tilføjer en blok,
 * kloner JavaScript den tilsvarende skabelon.
 *
 * Alternativet — at hente markup fra serveren ved hvert klik — ville koste
 * et netværkskald og en ekstra fil for præcis samme resultat.
 */
?>
<?php foreach (BlockRegistry::all() as $type => $label): ?>
    <?php
        $class    = BlockRegistry::get($type);
        $defaults = $class::defaultSettings();
        $dStyles  = $class::defaultStyles();
    ?>
    <template data-template-for="<?= e($type) ?>">
        <article class="ed-block" data-block-id="" data-block-type="<?= e($type) ?>">
            <span class="ed-block__label"><?= e($label) ?></span>
            <div class="ed-block__actions">
                <button type="button" class="ed-btn ed-btn--edit" data-action="edit"
                        aria-expanded="false">&#9998;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="up">&and;</button>
                <button type="button" class="ed-btn ed-btn--move" data-action="down">&or;</button>
                <button type="button" class="ed-btn ed-btn--delete" data-action="delete">&times;</button>
            </div>
            <div class="ed-block__preview">
                <?= $class::render($defaults, $dStyles, $context) ?>
            </div>
            <?= renderPanel($class, $defaults, $dStyles) ?>
        </article>
    </template>
<?php endforeach; ?>

<script src="editor.js"></script>
</body>
</html>