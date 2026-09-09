<?php
declare(strict_types=1);

/**
 * Al SQL, der rører tabellen `pages`.
 *
 * Ingen anden fil i systemet må skrive queries mod pages. Det er dét,
 * der gør adskillelsen mellem data og præsentation reel frem for en
 * hensigtserklæring.
 */
final class PageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Alle sider til oversigten "Dine sider".
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = 'SELECT id, title, slug, parent_id, status, sort_order,
                       last_published_at, created_at, updated_at
                FROM pages
                ORDER BY sort_order ASC, id ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * @return array<string, mixed>|null  null hvis siden ikke findes.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);

        // fetch() giver false ved ingen række; vi normaliserer til null,
        // så kaldende kode kun har ét "findes ikke"-udtryk at forholde sig til.
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, ?int $parentId = null): ?array
    {
        // parent_id er nullable, så en simpel "= :parent" ville aldrig
        // matche rod-sider. <=> er MariaDBs NULL-sikre sammenligning.
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pages WHERE slug = :slug AND parent_id <=> :parent'
        );
        $stmt->execute(['slug' => $slug, 'parent' => $parentId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Opretter en side og returnerer dens id.
     *
     * @throws InvalidArgumentException ved ugyldig slug.
     */
    public function create(
        string $title,
        string $slug,
        ?int $parentId = null,
        string $status = 'draft',
        ?int $templateId = null
    ): int {
        if (!Slug::isValid($slug)) {
            throw new InvalidArgumentException(
                "Ugyldig slug: '{$slug}'. Brug kun små bogstaver, tal og bindestreg."
            );
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO pages (title, slug, parent_id, status, sort_order,
                                created_from_template_id)
             VALUES (:title, :slug, :parent, :status, :sort_order, :template)'
        );

        $stmt->execute([
            'title'      => $title,
            'slug'       => $slug,
            'parent'     => $parentId,
            'status'     => $status,
            'sort_order' => $this->nextSortOrder($parentId),
            'template'   => $templateId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $title,
        string $slug,
        string $status,
        ?int $parentId = null
    ): void {
        if (!Slug::isValid($slug)) {
            throw new InvalidArgumentException(
                "Ugyldig slug: '{$slug}'. Brug kun små bogstaver, tal og bindestreg."
            );
        }

        $stmt = $this->pdo->prepare(
            'UPDATE pages
                SET title = :title, slug = :slug, status = :status,
                    parent_id = :parent
              WHERE id = :id'
        );

        $stmt->execute([
            'title'  => $title,
            'slug'   => $slug,
            'status' => $status,
            'parent' => $parentId,
            'id'     => $id,
        ]);
    }

    /**
     * Alle sider under en given side, uanset hvor dybt.
     *
     * Bruges to steder: til at holde en side ude af sin egen
     * forælder-dropdown, og til at afvise et flyt, der ville gøre en side
     * til sit eget barnebarn. Uden den kontrol ville forældrekæden blive
     * cyklisk, og siden kunne aldrig eksporteres.
     *
     * @return array<int, int>
     */
    public function descendantIds(int $pageId): array
    {
        $stmt = $this->pdo->query('SELECT id, parent_id FROM pages');
        $rows = $stmt->fetchAll();

        $children = [];

        foreach ($rows as $row) {
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : 0;
            $children[$parentId][] = (int) $row['id'];
        }

        $descendants = [];
        $queue       = $children[$pageId] ?? [];

        // Bredde-først frem for rekursion, så en cyklisk kæde i databasen
        // ikke kan udløse et uendeligt kald.
        while ($queue !== []) {
            $id = array_shift($queue);

            if (in_array($id, $descendants, true)) {
                continue;
            }

            $descendants[] = $id;

            foreach ($children[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        return $descendants;
    }

    /**
     * Sidens blokke fjernes automatisk af ON DELETE CASCADE.
     * Har siden undersider, afviser databasen sletningen (ON DELETE RESTRICT).
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Gemmer ny rækkefølge efter drag-and-drop i "Dine sider".
     *
     * @param array<int, int> $orderedIds Side-id'er i den ønskede rækkefølge.
     */
    public function reorder(array $orderedIds): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE pages SET sort_order = :sort_order WHERE id = :id'
            );

            foreach (array_values($orderedIds) as $position => $id) {
                $stmt->execute([
                    'sort_order' => ($position + 1) * 10,
                    'id'         => (int) $id,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            // Uden transaktionen kunne en fejl halvvejs efterlade listen
            // i en rækkefølge, der hverken er den gamle eller den nye.
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function markAsPublished(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE pages SET last_published_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Springet på 10 giver plads til manuel indsættelse mellem to sider,
     * uden at hele listen skal nummereres om.
     */
    private function nextSortOrder(?int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 10
               FROM pages
              WHERE parent_id <=> :parent'
        );
        $stmt->execute(['parent' => $parentId]);

        return (int) $stmt->fetchColumn();
    }
}