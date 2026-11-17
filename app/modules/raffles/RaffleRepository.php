<?php
declare(strict_types=1);

namespace App\Modules\Raffles;

use PDO;

final class RaffleRepository
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
        $sql = 'SELECT r.*,
                       (SELECT COUNT(*) FROM raffle_prizes rp WHERE rp.raffle_id = r.id) AS prizes_count,
                       (SELECT COUNT(*) FROM raffle_payment_methods rpm WHERE rpm.raffle_id = r.id) AS payment_methods_count
                FROM raffles r';
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(r.name LIKE ? OR r.slug LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== null && $status !== '') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM raffles WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $raffle = $statement->fetch();

        return $raffle === false ? null : $raffle;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug, ?int $exceptId = null): ?array
    {
        if ($exceptId === null) {
            $statement = $this->pdo->prepare('SELECT * FROM raffles WHERE slug = ? LIMIT 1');
            $statement->execute([$slug]);
        } else {
            $statement = $this->pdo->prepare('SELECT * FROM raffles WHERE slug = ? AND id <> ? LIMIT 1');
            $statement->execute([$slug, $exceptId]);
        }

        $raffle = $statement->fetch();

        return $raffle === false ? null : $raffle;
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM raffles')->fetchColumn();
    }

    /**
     * Sorteos visibles en la web pública.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publicRaffles(): array
    {
        $statuses = ['published', 'paused', 'sales_closed', 'drawing', 'drawn', 'results_published'];
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));

        $statement = $this->pdo->prepare(
            'SELECT r.*,
                    (SELECT COUNT(*) FROM raffle_prizes rp WHERE rp.raffle_id = r.id) AS prizes_count
             FROM raffles r
             WHERE r.status IN (' . $placeholders . ')
             ORDER BY r.is_featured DESC, r.featured_order ASC, r.published_at DESC, r.id DESC
             LIMIT 6'
        );
        $statement->execute($statuses);

        return $statement->fetchAll();
    }

    /**
     * Sorteo en borrador más reciente, solo para vista previa.
     *
     * @return array<string, mixed>|null
     */
    public function latestDraft(): ?array
    {
        $raffle = $this->pdo->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM raffle_prizes rp WHERE rp.raffle_id = r.id) AS prizes_count
             FROM raffles r
             WHERE r.status = 'draft'
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 1"
        )->fetch();

        return $raffle === false ? null : $raffle;
    }

    /**
     * Premios asignados a un sorteo, en orden de posición.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prizesFor(int $raffleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.public_code, p.name, p.description, p.prize_type,
                    p.estimated_value_cents, p.image_path, rp.position
             FROM raffle_prizes rp
             INNER JOIN prizes p ON p.id = rp.prize_id
             WHERE rp.raffle_id = ?
             ORDER BY rp.position ASC'
        );
        $statement->execute([$raffleId]);

        return $statement->fetchAll();
    }


    /**
     * @return array<string, mixed>|null
     */
    public function findByFeaturedOrder(int $order, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT id, name, featured_order FROM raffles WHERE is_featured = 1 AND featured_order = ?';
        $params = [$order];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $raffle = $statement->fetch();

        return $raffle === false ? null : $raffle;
    }

    public function countPublished(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM raffles WHERE status IN ('published', 'paused', 'sales_closed')"
        )->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO raffles
                (public_code, slug, name, summary, description, status, price_cents, currency,
                 total_tickets, max_tickets_per_participant, min_age, sales_start_at, sales_end_at,
                 draw_at, claim_deadline_at, current_rules_version_id, is_featured, featured_order,
                 cover_image_path, created_by, published_at, closed_at, drawn_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, NULL, NULL)'
        );
        $statement->execute([
            $data['public_code'],
            $data['slug'],
            $data['name'],
            $data['summary'],
            $data['description'],
            $data['status'],
            $data['price_cents'],
            $data['currency'],
            $data['total_tickets'],
            $data['max_tickets_per_participant'],
            $data['min_age'],
            $data['sales_start_at'],
            $data['sales_end_at'],
            $data['draw_at'],
            $data['claim_deadline_at'],
            $data['is_featured'],
            $data['featured_order'],
            $data['cover_image_path'],
            $data['created_by'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE raffles
             SET name = ?, slug = ?, summary = ?, description = ?, price_cents = ?,
                 total_tickets = ?, max_tickets_per_participant = ?, min_age = ?,
                 sales_start_at = ?, sales_end_at = ?, draw_at = ?, claim_deadline_at = ?,
                 is_featured = ?, featured_order = ?, cover_image_path = ?
             WHERE id = ?'
        );
        $statement->execute([
            $data['name'],
            $data['slug'],
            $data['summary'],
            $data['description'],
            $data['price_cents'],
            $data['total_tickets'],
            $data['max_tickets_per_participant'],
            $data['min_age'],
            $data['sales_start_at'],
            $data['sales_end_at'],
            $data['draw_at'],
            $data['claim_deadline_at'],
            $data['is_featured'],
            $data['featured_order'],
            $data['cover_image_path'],
            $id,
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $allowed = [
            'published_at',
            'closed_at',
            'drawn_at',
            'legal_checklist',
            'legal_confirmed_at',
            'legal_confirmed_by',
        ];
        $sets = ['status = ?'];
        $params = [$status];

        foreach ($extra as $column => $value) {
            if (in_array($column, $allowed, true)) {
                $sets[] = $column . ' = ?';
                $params[] = $value;
            }
        }

        $params[] = $id;

        $statement = $this->pdo->prepare('UPDATE raffles SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $statement->execute($params);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM raffles WHERE id = ?');
        $statement->execute([$id]);
    }

    public function updateCover(int $id, string $coverImagePath): void
    {
        $statement = $this->pdo->prepare('UPDATE raffles SET cover_image_path = ? WHERE id = ?');
        $statement->execute([$coverImagePath, $id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function rulesForRaffle(int $raffleId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM raffle_rules_versions WHERE raffle_id = ? ORDER BY version DESC LIMIT 1'
        );
        $statement->execute([$raffleId]);
        $rules = $statement->fetch();

        return $rules === false ? null : $rules;
    }

    public function createRulesVersion(int $raffleId, string $content, string $hash, int $actorId): int
    {
        $version = (int) $this->pdo->query(
            'SELECT COALESCE(MAX(version), 0) + 1 FROM raffle_rules_versions WHERE raffle_id = ' . $raffleId
        )->fetchColumn();

        $statement = $this->pdo->prepare(
            'INSERT INTO raffle_rules_versions
                (raffle_id, version, content, content_hash, status, published_at, created_by)
             VALUES (?, ?, ?, ?, ?, NULL, ?)'
        );
        $statement->execute([$raffleId, $version, $content, $hash, 'draft', $actorId]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateRules(int $id, string $content, string $hash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE raffle_rules_versions SET content = ?, content_hash = ? WHERE id = ?'
        );
        $statement->execute([$content, $hash, $id]);
    }

    public function publishRules(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE raffle_rules_versions SET status = 'published', published_at = NOW() WHERE id = ?"
        );
        $statement->execute([$id]);
    }

    public function deleteRulesForRaffle(int $raffleId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM raffle_rules_versions WHERE raffle_id = ?');
        $statement->execute([$raffleId]);
    }

    public function setCurrentRules(int $raffleId, int $rulesId): void
    {
        $statement = $this->pdo->prepare('UPDATE raffles SET current_rules_version_id = ? WHERE id = ?');
        $statement->execute([$rulesId, $raffleId]);
    }

    public function clearCurrentRules(int $raffleId): void
    {
        $statement = $this->pdo->prepare('UPDATE raffles SET current_rules_version_id = NULL WHERE id = ?');
        $statement->execute([$raffleId]);
    }

    /**
     * @return int[]
     */
    public function prizeIds(int $raffleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT prize_id FROM raffle_prizes WHERE raffle_id = ? ORDER BY position ASC'
        );
        $statement->execute([$raffleId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return int[]
     */
    public function paymentMethodIds(int $raffleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT payment_method_id FROM raffle_payment_methods WHERE raffle_id = ? ORDER BY sort_order ASC'
        );
        $statement->execute([$raffleId]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param int[] $prizeIds
     */
    public function syncPrizes(int $raffleId, array $prizeIds): void
    {
        $delete = $this->pdo->prepare('DELETE FROM raffle_prizes WHERE raffle_id = ?');
        $delete->execute([$raffleId]);

        $insert = $this->pdo->prepare(
            'INSERT INTO raffle_prizes (raffle_id, prize_id, position, conditions) VALUES (?, ?, ?, NULL)'
        );

        $position = 1;
        foreach ($prizeIds as $prizeId) {
            $insert->execute([$raffleId, (int) $prizeId, $position]);
            $position++;
        }
    }

    /**
     * @param int[] $methodIds
     */
    public function syncPaymentMethods(int $raffleId, array $methodIds): void
    {
        $delete = $this->pdo->prepare('DELETE FROM raffle_payment_methods WHERE raffle_id = ?');
        $delete->execute([$raffleId]);

        $insert = $this->pdo->prepare(
            'INSERT INTO raffle_payment_methods
                (raffle_id, payment_method_id, instructions_override, is_active, sort_order)
             VALUES (?, ?, NULL, 1, ?)'
        );

        $sortOrder = 0;
        foreach ($methodIds as $methodId) {
            $insert->execute([$raffleId, (int) $methodId, $sortOrder]);
            $sortOrder++;
        }
    }

    public function countOrders(int $raffleId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE raffle_id = ?');
        $statement->execute([$raffleId]);

        return (int) $statement->fetchColumn();
    }

    public function countTickets(int $raffleId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM tickets WHERE raffle_id = ?');
        $statement->execute([$raffleId]);

        return (int) $statement->fetchColumn();
    }

    public function countNumbers(int $raffleId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM raffle_numbers WHERE raffle_id = ?');
        $statement->execute([$raffleId]);

        return (int) $statement->fetchColumn();
    }
}
