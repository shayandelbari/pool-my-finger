<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Session;
use App\Backend\Models\User;
use DateTime;
use PDO;
use PDOException;

class SessionRepository
{
    public static function getSessionById(int $id): ?Session
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT
                    s.id,
                    s.token,
                    s.created_at,
                    s.expires_at,
                    s.revoked,
                    u.id AS user_id,
                    u.username,
                    u.created_at AS user_created_at
                 FROM sessions s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? self::hydrateSession($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function getSessionByTokenHash(string $tokenHash): ?Session
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT
                    s.id,
                    s.token,
                    s.created_at,
                    s.expires_at,
                    s.revoked,
                    u.id AS user_id,
                    u.username,
                    u.created_at AS user_created_at
                 FROM sessions s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.token = :token"
            );
            $stmt->execute([':token' => $tokenHash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? self::hydrateSession($row) : null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function createSession(User $user, string $tokenHash, DateTime $expiresAt): int
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "INSERT INTO sessions (user_id, token, expires_at)
                 VALUES (:user_id, :token, :expires_at)"
            );
            $stmt->execute([
                ':user_id' => $user->getId(),
                ':token' => $tokenHash,
                ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);

            return (int) $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public static function revokeSessionByTokenHash(string $tokenHash): bool
    {
        try {
            $conn = \db();
            if (!self::sessionTokenExists($conn, $tokenHash)) {
                return false;
            }

            $stmt = $conn->prepare(
                "UPDATE sessions
                 SET revoked = 1
                 WHERE token = :token"
            );
            $stmt->execute([':token' => $tokenHash]);

            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function revokeAllSessionsForUser(int $userId): int
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "UPDATE sessions
                 SET revoked = 1
                 WHERE user_id = :user_id
                   AND revoked = 0"
            );
            $stmt->execute([':user_id' => $userId]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    public static function deleteSessionByTokenHash(string $tokenHash): bool
    {
        try {
            $conn = \db();
            if (!self::sessionTokenExists($conn, $tokenHash)) {
                return false;
            }

            $stmt = $conn->prepare("DELETE FROM sessions WHERE token = :token");
            $stmt->execute([':token' => $tokenHash]);

            return true;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function deleteExpiredSessions(): int
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare("DELETE FROM sessions WHERE expires_at <= NOW()");
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydrateSession(array $row): Session
    {
        $user = new User(
            (int) $row['user_id'],
            (string) $row['username'],
            '',
            new DateTime((string) $row['user_created_at'])
        );

        return new Session(
            (int) $row['id'],
            $user,
            (string) $row['token'],
            new DateTime((string) $row['created_at']),
            new DateTime((string) $row['expires_at']),
            (bool) $row['revoked']
        );
    }

    private static function sessionTokenExists(PDO $conn, string $token): bool
    {
        $stmt = $conn->prepare("SELECT 1 FROM sessions WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);

        return (bool) $stmt->fetchColumn();
    }
}

\class_alias(__NAMESPACE__ . '\\SessionRepository', 'SessionRepository');
