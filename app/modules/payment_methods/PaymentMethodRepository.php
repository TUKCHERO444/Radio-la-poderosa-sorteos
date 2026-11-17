<?php
declare(strict_types=1);

namespace App\Modules\PaymentMethods;

use PDO;

final class PaymentMethodRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $search = null, ?string $type = null, ?string $status = null): array
    {
        $sql = 'SELECT id, name, type, provider, holder_name, holder_document, phone, bank_name,
                       account_number, cci, qr_image_path, contact_phone, instructions, is_active,
                       created_at, updated_at,
                       (SELECT COUNT(*) FROM raffle_payment_methods rpm WHERE rpm.payment_method_id = payment_methods.id) AS assignments_count,
                       (SELECT COUNT(*) FROM payments p WHERE p.payment_method_id = payment_methods.id) AS payments_count
                FROM payment_methods';
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(name LIKE ? OR holder_name LIKE ? OR provider LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($type === 'wallet' || $type === 'bank') {
            $where[] = 'type = ?';
            $params[] = $type;
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
        $statement = $this->pdo->prepare('SELECT * FROM payment_methods WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $method = $statement->fetch();

        return $method === false ? null : $method;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeAll(): array
    {
        return $this->pdo->query(
            'SELECT id, name, type, provider, holder_name, phone, bank_name, account_number,
                    cci, qr_image_path, contact_phone, instructions
             FROM payment_methods WHERE is_active = 1 ORDER BY type ASC, name ASC'
        )->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM payment_methods')->fetchColumn();
    }

    public function countAssigned(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM raffle_payment_methods WHERE payment_method_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    public function countPayments(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM payments WHERE payment_method_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO payment_methods
                (name, type, provider, holder_name, holder_document, phone, bank_name,
                 account_number, cci, qr_image_path, contact_phone, instructions, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['name'],
            $data['type'],
            $data['provider'],
            $data['holder_name'],
            $data['holder_document'],
            $data['phone'],
            $data['bank_name'],
            $data['account_number'],
            $data['cci'],
            $data['qr_image_path'],
            $data['contact_phone'],
            $data['instructions'],
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
            'UPDATE payment_methods
             SET name = ?, type = ?, provider = ?, holder_name = ?, holder_document = ?,
                 phone = ?, bank_name = ?, account_number = ?, cci = ?, qr_image_path = ?,
                 contact_phone = ?, instructions = ?, is_active = ?
             WHERE id = ?'
        );
        $statement->execute([
            $data['name'],
            $data['type'],
            $data['provider'],
            $data['holder_name'],
            $data['holder_document'],
            $data['phone'],
            $data['bank_name'],
            $data['account_number'],
            $data['cci'],
            $data['qr_image_path'],
            $data['contact_phone'],
            $data['instructions'],
            $data['is_active'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM payment_methods WHERE id = ?');
        $statement->execute([$id]);
    }

    public function toggle(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE payment_methods SET is_active = ? WHERE id = ?');
        $statement->execute([$active ? 1 : 0, $id]);
    }
}
