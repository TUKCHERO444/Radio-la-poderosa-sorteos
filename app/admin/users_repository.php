<?php
declare(strict_types=1);

function users_all(?string $search = null): array
{
    $columns = 'SELECT id, username, name, role, last_login_at, created_at
                FROM users';

    if ($search !== null && $search !== '') {
        $statement = db()->prepare(
            $columns . ' WHERE username LIKE ? OR name LIKE ? ORDER BY id ASC'
        );
        $like = '%' . $search . '%';
        $statement->execute([$like, $like]);

        return $statement->fetchAll();
    }

    return db()->query($columns . ' ORDER BY id ASC')->fetchAll();
}

function user_find(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT id, username, name, role, password_hash, last_login_at, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $statement->execute([$id]);
    $user = $statement->fetch();

    return $user === false ? null : $user;
}

function user_find_by_username(string $username): ?array
{
    $statement = db()->prepare(
        'SELECT id, username, name, role, password_hash, last_login_at, created_at, updated_at
         FROM users
         WHERE username = ?
         LIMIT 1'
    );
    $statement->execute([$username]);
    $user = $statement->fetch();

    return $user === false ? null : $user;
}

function user_username_exists(string $username, ?int $exceptId = null): bool
{
    if ($exceptId === null) {
        $statement = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $statement->execute([$username]);
    } else {
        $statement = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $statement->execute([$username, $exceptId]);
    }

    return (int) $statement->fetchColumn() > 0;
}

function user_create(string $username, ?string $name, string $passwordHash, string $role): int
{
    $statement = db()->prepare(
        'INSERT INTO users (username, name, password_hash, role) VALUES (?, ?, ?, ?)'
    );
    $statement->execute([$username, $name, $passwordHash, $role]);

    return (int) db()->lastInsertId();
}

function user_update(int $id, string $username, ?string $name, string $role, ?string $passwordHash = null): void
{
    if ($passwordHash === null) {
        $statement = db()->prepare(
            'UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?'
        );
        $statement->execute([$username, $name, $role, $id]);
        return;
    }

    $statement = db()->prepare(
        'UPDATE users SET username = ?, name = ?, role = ?, password_hash = ? WHERE id = ?'
    );
    $statement->execute([$username, $name, $role, $passwordHash, $id]);
}

function user_update_password(int $id, string $passwordHash): void
{
    $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $statement->execute([$passwordHash, $id]);
}

function user_touch_login(int $id): void
{
    $statement = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $statement->execute([$id]);
}

function user_delete(int $id): void
{
    $statement = db()->prepare('DELETE FROM users WHERE id = ?');
    $statement->execute([$id]);
}

function users_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

function users_admin_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}
