<?php
declare(strict_types=1);

/**
 * Tegner editorens formularfelter ud fra blokkenes skemaer.
 *
 * Ligger som en klasse frem for som funktioner i editor.php, fordi
 * rendering af et side-felt kræver listen over sider. Den skal sendes ind
 * ét sted, ikke hentes fra en global variabel hver gang et felt tegnes.
 *
 * Skemaet er stadig den eneste sandhed om, hvilke felter der findes.
 * Denne klasse oversætter det til brugerflade — den opfinder ingenting.
 */
final class FieldRenderer
{
    /**
     * @param array<int, string> $pageChoices Side-id => titel.
     * @param string             $basePath    Projektets sti under htdocs,
     *                                        så miniaturebilleder kan vises.
     */
    public function __construct(
        private readonly array $pageChoices = [],
        private readonly string $basePath = ''
    ) {
    }

    /**
     * Hele redigeringspanelet for én blok.
     *
     * @param class-string<BlockInterface> $class
     * @param array<string, mixed>         $settings
     * @param array<string, mixed>         $styles
     */
    public function panel(string $class, array $settings, array $styles): string
    {
        $html = '<div class="ed-panel" hidden>';

        $html .= '<fieldset class="ed-group"><legend>Indhold</legend>';

        foreach ($class::getSchema() as $name => $field) {
            $value = $settings[$name] ?? '';

            $html .= ($field['type'] ?? '') === 'repeater'
                ? $this->repeater($name, $field, is_array($value) ? $value : [])
                : $this->field('settings', $name, $field, $value);
        }

        $html .= '</fieldset>';

        $styleSchema = $class::getStyleSchema();

        if ($styleSchema !== []) {
            $html .= '<fieldset class="ed-group"><legend>Udseende</legend>';

            foreach ($styleSchema as $name => $field) {
                $html .= $this->field('styles', $name, $field, $styles[$name] ?? '');
            }

            $html .= '</fieldset>';
        }

        return $html . '</div>';
    }

    /**
     * Ét formularfelt.
     *
     * @param array<string, mixed> $field
     */
    public function field(string $scope, string $name, array $field, mixed $value): string
    {
        // Id'et skal være unikt på tværs af alle blokke på siden, så
        // etiketten peger på det rigtige felt.
        $id    = 'f_' . $scope . '_' . $name . '_' . bin2hex(random_bytes(3));
        $label = (string) ($field['label'] ?? $name);

        $attributes = 'id="' . e($id) . '"'
            . ' data-scope="' . e($scope) . '"'
            . ' data-field="' . e($name) . '"';

        return '<p class="ed-field">'
            . '<label for="' . e($id) . '">' . e($label) . '</label>'
            . $this->input($attributes, $field, $value)
            . '</p>';
    }

    /**
     * @param array<string, mixed> $field
     */
    private function input(string $attributes, array $field, mixed $value): string
    {
        return match ($field['type'] ?? 'text') {
            'textarea' => '<textarea ' . $attributes . ' rows="4">'
                . e((string) $value) . '</textarea>',

            'color' => '<input type="color" ' . $attributes
                . ' value="' . e($value !== '' ? (string) $value : '#000000') . '">',

            'number' => '<input type="number" ' . $attributes
                . ' value="' . e((string) $value) . '"'
                . ' min="' . (int) ($field['min'] ?? 0) . '"'
                . ' max="' . (int) ($field['max'] ?? 9999) . '">',

            'select' => $this->select($attributes, $field['options'] ?? [], (string) $value),

            // Sider vælges fra en liste frem for at skrives som adresse.
            // Så kan brugeren ikke stave forkert, og linket overlever, at
            // målsiden får en ny slug.
            'page' => $this->pageSelect($attributes, (int) $value),

            'image' => $this->imagePicker($attributes, (string) $value),

            default => '<input type="text" ' . $attributes
                . ' value="' . e((string) $value) . '">',
        };
    }

    /**
     * @param array<int, string> $options
     */
    private function select(string $attributes, array $options, string $value): string
    {
        $html = '<select ' . $attributes . '>';

        foreach ($options as $option) {
            $html .= '<option value="' . e($option) . '"'
                . ($value === (string) $option ? ' selected' : '')
                . '>' . e($option) . '</option>';
        }

        return $html . '</select>';
    }

    private function pageSelect(string $attributes, int $selected): string
    {
        $html = '<select ' . $attributes . '>'
            . '<option value="0">— ingen —</option>';

        foreach ($this->pageChoices as $id => $title) {
            $html .= '<option value="' . (int) $id . '"'
                . ($selected === (int) $id ? ' selected' : '')
                . '>' . e($title) . '</option>';
        }

        return $html . '</select>';
    }

    /**
     * Et billedfelt: miniature, filvælger og den gemte sti.
     *
     * Stien står stadig i et almindeligt tekstfelt — det er dét, gemme-
     * flowet læser, og det gør feltet identisk med alle andre felter set
     * fra JavaScript. Feltet er skrivebeskyttet, fordi værdien nu kommer
     * fra en upload; skal en sti rettes i hånden, kan det gøres i
     * databasen.
     *
     * Selve upload-knappen og den skjulte fil-input håndteres af
     * editor.js. Her tegnes kun markup'en.
     */
    private function imagePicker(string $attributes, string $value): string
    {
        $thumbnail = $value !== ''
            ? $this->basePath . '/' . ltrim($value, '/')
            : '';

        return '<span class="ed-image">'
            . '<span class="ed-image__preview">'
            . ($thumbnail !== ''
                ? '<img src="' . e($thumbnail) . '" alt="">'
                : '<span class="ed-image__placeholder">Intet billede</span>')
            . '</span>'
            . '<input type="text" ' . $attributes
            . ' class="ed-image__path" value="' . e($value) . '" readonly'
            . ' placeholder="Ingen fil valgt">'
            // Fil-inputtet ligger inde i en <label>. Klikker man på
            // etiketten, aktiverer browseren selv den skjulte input.
            //
            // Alternativet — en <button> der kalder .click() på inputtet
            // via JavaScript — er skrøbeligt, fordi browsere er
            // restriktive omkring, hvornår et programmatisk klik må åbne
            // en filvælger. Her er der ingen JavaScript involveret.
            . '<label class="ed-image__btn">'
            . '<span class="ed-image__btn-text">Vælg fil</span>'
            . '<input type="file" class="ed-image__file" accept="image/*" hidden>'
            . '</label>'
            . '</span>';
    }

    /**
     * Et repeater-felt: et vilkårligt antal ens rækker.
     *
     * Bruges til punktlister og navigationslinks. Rækkerne ligger i
     * blokkens egen JSON, ikke som selvstændige blokke i databasen — det
     * er dét, der sparer os for indlejrede blokke med parent_id, og dermed
     * for rekursiv rendering og forældreløse rækker ved sletning.
     *
     * Den tomme <template> nederst bruges, når brugeren tilføjer en række.
     * Så bygger JavaScript ikke felter selv; det kloner det, PHP allerede
     * har tegnet ud fra skemaet.
     *
     * @param array<string, mixed>             $field
     * @param array<int, array<string, mixed>> $rows
     */
    public function repeater(string $name, array $field, array $rows): string
    {
        $html = '<div class="ed-repeater" data-repeater="' . e($name) . '">'
            . '<span class="ed-repeater__label">'
            . e((string) ($field['label'] ?? $name)) . '</span>'
            . '<div class="ed-repeater__rows">';

        foreach ($rows as $row) {
            $html .= $this->row($field['fields'] ?? [], is_array($row) ? $row : []);
        }

        return $html . '</div>'
            . '<button type="button" class="ed-repeater__add" data-action="add-row">'
            . '+ Tilføj række</button>'
            . '<template data-row-template>'
            . $this->row($field['fields'] ?? [], [])
            . '</template>'
            . '</div>';
    }

    /**
     * @param array<string, array<string, mixed>> $subSchema
     * @param array<string, mixed>                $row
     */
    private function row(array $subSchema, array $row): string
    {
        $html = '<div class="ed-row">';

        foreach ($subSchema as $name => $field) {
            $attributes = 'data-rfield="' . e($name) . '"'
                . ' aria-label="' . e((string) ($field['label'] ?? $name)) . '"';

            $html .= $this->input($attributes, $field, $row[$name] ?? '');
        }

        return $html
            . '<button type="button" class="ed-btn ed-btn--delete"'
            . ' data-action="remove-row" aria-label="Fjern række">&times;</button>'
            . '</div>';
    }
}