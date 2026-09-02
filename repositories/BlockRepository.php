<?php
declare(strict_types=1);

/**
 * Al SQL, der rører tabellen `page_blocks`.
 *
 * Repositoriet er også det eneste sted, der kender til, at settings og
 * styles er gemt som JSON-tekst. Alt uden for denne fil arbejder med
 * almindelige PHP-arrays. Skifter I engang lagringsform, er det kun her,
 * der skal rettes.
 */
final class BlockRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Alle blokke på en side, i visningsrækkefølge og med JSON udpakket.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByPage(int $pageId, bool $onlyVisible = false): array
    {
        $sql = 'SELECT id, page_id, block_type, sort_order,
                       settings, styles, is_visible
                  FROM page_blocks
                 WHERE page_id = :page_id';

        // Editoren viser skjulte blokke (så de kan slås til igen);
        // den offentlige side og eksporten gør ikke.
        if ($onlyVisible) {
            $sql .= ' AND is_visible = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['page_id' => $pageId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM page_blocks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $styles
     */
    public function insert(
        int $pageId,
        string $blockType,
        array $settings,
        array $styles = [],
        ?int $sortOrder = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO page_blocks (page_id, block_type, sort_order, settings, styles)
             VALUES (:page_id, :block_type, :sort_order, :settings, :styles)'
        );

        $stmt->execute([
            'page_id'    => $pageId,
            'block_type' => $blockType,
            'sort_order' => $sortOrder ?? $this->nextSortOrder($pageId),
            'settings'   => $this->encode($settings),
            'styles'     => $this->encode($styles),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Opdaterer en bloks indhold, styling og eventuelt dens placering.
     *
     * page_id indgaar i WHERE, saa et manipuleret blok-id fra browseren
     * ikke kan skrive til en blok, der hoerer til en anden side.
     *
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $styles
     */
    public function update(
        int $id,
        int $pageId,
        array $settings,
        array $styles,
        ?int $sortOrder = null
    ): void {
        $sql = 'UPDATE page_blocks
                   SET settings = :settings, styles = :styles';

        $params = [
            'settings' => $this->encode($settings),
            'styles'   => $this->encode($styles),
            'id'       => $id,
            'page_id'  => $pageId,
        ];

        if ($sortOrder !== null) {
            $sql .= ', sort_order = :sort_order';
            $params['sort_order'] = $sortOrder;
        }

        $sql .= ' WHERE id = :id AND page_id = :page_id';

        $this->pdo->prepare($sql)->execute($params);
    }

    /**
     * Id'erne paa alle blokke, der hoerer til en side.
     *
     * Bruges af gemme-flowet til at finde ud af, hvilke blokke brugeren
     * har slettet: de findes i databasen, men mangler i det, browseren
     * sender ind.
     *
     * @return array<int, int>
     */
    public function idsForPage(int $pageId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM page_blocks WHERE page_id = :page_id'
        );
        $stmt->execute(['page_id' => $pageId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Sletter flere blokke paa en gang.
     *
     * @param array<int, int> $ids
     */
    public function deleteMany(int $pageId, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        // Placeholdere bygges ud fra ANTALLET af id'er, aldrig ud fra
        // deres vaerdier. Selve tallene sendes stadig som parametre.
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->pdo->prepare(
            "DELETE FROM page_blocks
              WHERE page_id = ? AND id IN ({$placeholders})"
        );

        $stmt->execute(array_merge([$pageId], array_map('intval', $ids)));
    }

    public function setVisibility(int $id, bool $isVisible): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE page_blocks SET is_visible = :visible WHERE id = :id'
        );
        $stmt->execute(['visible' => $isVisible ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM page_blocks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Gemmer blokkenes rækkefølge på en side.
     *
     * page_id indgår bevidst i WHERE. Uden den kunne et manipuleret
     * id-array flytte rundt på blokke, der hører til en anden side.
     *
     * @param array<int, int> $orderedIds
     */
    public function reorder(int $pageId, array $orderedIds): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE page_blocks
                    SET sort_order = :sort_order
                  WHERE id = :id AND page_id = :page_id'
            );

            foreach (array_values($orderedIds) as $position => $id) {
                $stmt->execute([
                    'sort_order' => ($position + 1) * 10,
                    'id'         => (int) $id,
                    'page_id'    => $pageId,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function nextSortOrder(int $pageId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 10
               FROM page_blocks
              WHERE page_id = :page_id'
        );
        $stmt->execute(['page_id' => $pageId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Oversætter en databaserække til det format resten af koden bruger.
     */
    private function hydrate(array $row): array
    {
        $row['id']         = (int) $row['id'];
        $row['page_id']    = (int) $row['page_id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_visible'] = (bool) $row['is_visible'];
        $row['settings']   = $this->decode($row['settings'] ?? '{}');
        $row['styles']     = $this->decode($row['styles'] ?? '{}');

        return $row;
    }

    private function decode(string $json): array
    {
        $data = json_decode($json, true);

        // Databasen har en JSON_VALID-constraint, så det her burde ikke
        // kunne ske. Falder vi alligevel igennem, er et tomt array bedre
        // end en fatal fejl midt i en sidevisning.
        return is_array($data) ? $data : [];
    }

    private function encode(array $data): string
    {
        // UNESCAPED_UNICODE holder æøå læsbare i databasen frem for at
        // gemme dem som \u00e6-sekvenser.
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}