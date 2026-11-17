<?php
declare(strict_types=1);

namespace App\Modules\Prizes;

use PDO;

final class PrizeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $search = null, ?string $status = null): array
    {
        $sql = 'SELECT id, public_code, name, description, prize_type, estimated_value_cents,
                       image_path, delivery_method, is_active, created_at, updated_at,
                       (SELECT COUNT(*) FROM raffle_prizes rp WHERE rp.prize_id = prizes.id) AS assignments_count
                FROM prizes';
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = 0';
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY is_active DESC, name ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM prizes WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $prize = $statement->fetch();

        return $prize === false ? null : $prize;
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM prizes')->fetchColumn();
    }

    public function countAssigned(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM raffle_prizes WHERE prize_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeAll(): array
    {
        return $this->pdo->query(
            'SELECT id, public_code, name, prize_type, estimated_value_cents, image_path, is_active
             FROM prizes WHERE is_active = 1 ORDER BY name ASC'
        )->fetchAll();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO prizes
                (public_code, name, description, prize_type, estimated_value_cents, image_path,
                 delivery_method, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['public_code'],
            $data['name'],
            $data['description'],
            $data['prize_type'],
            $data['estimated_value_cents'],
            $data['image_path'],
            $data['delivery_method'],
            $data['is_active'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE prizes
             SET name = ?, description = ?, prize_type = ?, estimated_value_cents = ?,
                 image_path = ?, delivery_method = ?, is_active = ?
             WHERE id = ?'
        );
        $statement->execute([
            $data['name'],
            $data['description'],
            $data['prize_type'],
            $data['estimated_value_cents'],
            $data['image_path'],
            $data['delivery_method'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM prizes WHERE id = ?');
        $statement->execute([$id]);
    }

    public function toggle(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE prizes SET is_active = ? WHERE id = ?');
        $statement->execute([$active ? 1 : 0, $id]);
    }
}
