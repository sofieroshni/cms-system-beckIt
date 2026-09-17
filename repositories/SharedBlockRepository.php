<?php
declare(strict_types=1);

/**
 * Al SQL, der rører tabellen `shared_blocks`.
 *
 * Spejler BlockRepository bevidst: samme JSON-håndtering, samme
 * hydrering. Det gør, at en delt blok kan sendes gennem PageRenderer
 * uden oversættelse undervejs.
 */
final class SharedBlockRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = 'SELECT id, name, block_type, settings, styles, updated_at
                  FROM shared_blocks
                 ORDER BY block_type ASC, name ASC';

        return array_map([$this, 'hydrate'], $this->pdo->query($sql)->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shared_blocks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Opretter en delt blok med bloktypens standardværdier.
     *
     * @throws InvalidArgumentException ved ukendt bloktype.
     */
    public function create(string $name, string $blockType): int
    {
        $class = BlockRegistry::get($blockType);

        if ($class === null) {
            throw new InvalidArgumentException('Ukendt bloktype.');
        }

        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Den delte blok skal have et navn.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO shared_blocks (name, block_type, settings, styles)
             VALUES (:name, :block_type, :settings, :styles)'
        );

        $stmt->execute([
            'name'       => mb_substr($name, 0, 150, 'UTF-8'),
            'block_type' => $blockType,
            'settings'   => $this->encode($class::defaultSettings()),
            'styles'     => $this->encode($class::defaultStyles()),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Bloktypen kan ikke ændres. Felterne i settings hører til netop den
     * type, og et skift ville efterlade data, ingen blok kan læse.
     *
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $styles
     */
    public function update(int $id, string $name, array $settings, array $styles): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Den delte blok skal have et navn.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE shared_blocks
                SET name = :name, settings = :settings, styles = :styles
              WHERE id = :id'
        );

        $stmt->execute([
            'name'     => mb_substr($name, 0, 150, 'UTF-8'),
            'settings' => $this->encode($settings),
            'styles'   => $this->encode($styles),
            'id'       => $id,
        ]);
    }

    /**
     * Henvisningerne på siderne forsvinder automatisk via ON DELETE
     * CASCADE.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM shared_blocks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Hvor mange sider, der bruger den delte blok.
     *
     * Vises før sletning, så brugeren ved hvad der forsvinder.
     */
    public function usageCount(int $id): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM page_blocks WHERE shared_block_id = :id'
        );
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $row['id']       = (int) $row['id'];
        $row['settings'] = $this->decode($row['settings'] ?? '{}');
        $row['styles']   = $this->decode($row['styles'] ?? '{}');

        return $row;
    }

    private function decode(string $json): array
    {
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    private function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}