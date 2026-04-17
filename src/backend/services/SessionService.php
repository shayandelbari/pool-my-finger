<?php

namespace App\Backend\Services;

use App\Backend\Models\Session;
use App\Backend\Models\User;
use App\Backend\Repositories\SessionRepository;
use App\Backend\Services\Exceptions\SessionValidationException;
use DateInterval;
use DateTime;

class SessionService
{
    private const DEFAULT_TTL_DAYS = 7;

    /**
     * @return array{sessionId:int, token:string, expiresAt:DateTime}
     */
    public static function createSession(User $user): array
    {
        $token = self::generateToken();
        $tokenHash = self::hashToken($token);
        $expiresAt = self::buildExpiryDate();

        $sessionId = SessionRepository::createSession($user, $tokenHash, $expiresAt);

        return [
            'sessionId' => $sessionId,
            'token' => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * @throws SessionValidationException
     */
    public static function validateSessionToken(string $token): Session
    {
        $tokenHash = self::hashToken($token);
        $session = SessionRepository::getSessionByTokenHash($tokenHash);

        if ($session === null) {
            throw new SessionValidationException('Session not found.');
        }

        if ($session->isRevoked()) {
            throw new SessionValidationException('Session revoked.');
        }

        if ($session->getExpires() <= new DateTime()) {
            throw new SessionValidationException('Session expired.');
        }

        return $session;
    }

    public static function logoutCurrentSession(string $token): bool
    {
        $tokenHash = self::hashToken($token);
        return SessionRepository::revokeSessionByTokenHash($tokenHash);
    }

    public static function logoutAllUserSessions(int $userId): int
    {
        return SessionRepository::revokeAllSessionsForUser($userId);
    }

    public static function cleanExpiredSessions(): int
    {
        return SessionRepository::deleteExpiredSessions();
    }

    private static function buildExpiryDate(): DateTime
    {
        $expiresAt = new DateTime();
        $expiresAt->add(new DateInterval('P' . self::DEFAULT_TTL_DAYS . 'D'));

        return $expiresAt;
    }

    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

\class_alias(__NAMESPACE__ . '\\SessionService', 'SessionService');
