<?php
declare(strict_types=1);

/**
 * Al SQL, der rører `page_templates` og `template_blocks`.
 *
 * Selve materialiseringen — at kopiere en skabelons blokke ned på en ny
 * side — hører til i fase 5. Her ligger kun læsningen.
 */
final class TemplateRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Skabeloner til "vælg skabelon"-skærmen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActive(): array
    {
        $sql = 'SELECT id, slug, name, description, thumbnail
                  FROM page_templates
                 WHERE is_active = 1
                 ORDER BY sort_order ASC, name ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM page_templates WHERE id = :id AND is_active = 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * En skabelons blokke med JSON udpakket. Nøglerne hedder bevidst
     * `settings` og `styles` — altså det samme som i BlockRepository —
     * så kopieringen i fase 5 bliver en direkte videreførsel uden
     * omdøbning undervejs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBlocks(int $templateId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT block_type, sort_order, default_settings, default_styles
               FROM template_blocks
              WHERE template_id = :template_id
              ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['template_id' => $templateId]);

        return array_map(
            static function (array $row): array {
                $settings = json_decode($row['default_settings'] ?? '{}', true);
                $styles   = json_decode($row['default_styles'] ?? '{}', true);

                return [
                    'block_type' => $row['block_type'],
                    'sort_order' => (int) $row['sort_order'],
                    'settings'   => is_array($settings) ? $settings : [],
                    'styles'     => is_array($styles) ? $styles : [],
                ];
            },
            $stmt->fetchAll()
        );
    }
}