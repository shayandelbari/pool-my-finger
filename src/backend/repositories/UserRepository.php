<?php

namespace App\Backend\Repositories;

use App\Backend\Models\User;
use DateTime;
use PDO;
use PDOException;

class UserRepository
{
    /**
     * @return array{id:int, username:string, password_hash:string, created_at:string}|null
     */
    public static function getUserAuthRecordByUsername(string $username): ?array
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT id, username, password_hash, created_at
                 FROM users
                 WHERE username = :username"
            );
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return [
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
                'password_hash' => (string) $row['password_hash'],
                'created_at' => (string) $row['created_at'],
            ];
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @return User[]
     */
    public static function getAllUsers(): array
    {
        try {
            $conn = \db();
            $stmt = $conn->query(
                "SELECT id, username, created_at
                 FROM users
                 ORDER BY username"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = [];
            foreach ($rows as $row) {
                $users[] = self::hydrateSafeUser($row);
            }

            return $users;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function getUserById(int $id): ?User
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT id, username, created_at
                 FROM users
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? self::hydrateSafeUser($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function getUserByUsername(string $username): ?User
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT id, username, created_at
                 FROM users
                 WHERE username = :username"
            );
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? self::hydrateSafeUser($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function verifyCredentials(string $username, string $plainPassword): ?User
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT id, username, password_hash, created_at
                 FROM users
                 WHERE username = :username"
            );
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            if (!password_verify($plainPassword, (string) $row['password_hash'])) {
                return null;
            }

            return self::hydrateSafeUser($row);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function createUser(string $username, string $passwordHash): int
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "INSERT INTO users (username, password_hash)
                 VALUES (:username, :password_hash)"
            );
            $stmt->execute([
                ':username' => $username,
                ':password_hash' => $passwordHash,
            ]);

            return (int) $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public static function deleteUser(int $userId): bool
    {
        try {
            $conn = \db();
            if (!self::userExists($conn, $userId)) {
                return false;
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrateSafeUser(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['username'],
            '',
            new DateTime((string) $row['created_at'])
        );
    }

    private static function userExists(PDO $conn, int $id): bool
    {
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }
}

\class_alias(__NAMESPACE__ . '\\UserRepository', 'UserRepository');
