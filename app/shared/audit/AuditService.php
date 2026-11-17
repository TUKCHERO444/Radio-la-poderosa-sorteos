<?php
declare(strict_types=1);

namespace App\Shared\Audit;

use PDO;

final class AuditService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function record(
        string $action,
        string $entityType,
        ?int $entityId,
        array $before,
        array $after,
        ?int $actorId,
        string $actorType = 'admin'
    ): void {
        $previousHash = $this->pdo->query('SELECT hash FROM audit_logs ORDER BY id DESC LIMIT 1')->fetchColumn();
        $previousHash = is_string($previousHash) && $previousHash !== '' ? $previousHash : null;

        $payload = json_encode([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'created_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $hash = hash('sha256', ($previousHash ?? '') . ($payload !== false ? $payload : ''));

        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs
                (actor_type, actor_id, action, entity_type, entity_id, before_json, after_json,
                 ip, user_agent, created_at, previous_hash, hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $actorType,
            $actorId,
            $action,
            $entityType,
            $entityId,
            $before !== [] ? json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $after !== [] ? json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            date('Y-m-d H:i:s'),
            $previousHash,
            $hash,
        ]);
    }
}
