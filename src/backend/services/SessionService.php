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

    // Service layer: owns session creation because token generation, hashing, and expiry policy are business rules.
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

    // Service layer: validates token state here because expiration and revocation are application rules, not controller concerns.
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

    // Service layer: revokes the current session because it must hash the token and apply the session lifecycle policy.
    public static function logoutCurrentSession(string $token): bool
    {
        $tokenHash = self::hashToken($token);
        return SessionRepository::revokeSessionByTokenHash($tokenHash);
    }

    // Service layer: bulk revocation is a user-session policy decision and belongs above the repository.
    public static function logoutAllUserSessions(int $userId): int
    {
        return SessionRepository::revokeAllSessionsForUser($userId);
    }

    // Service layer: cleanup stays here because expiration policy should live with the rest of session lifecycle logic.
    public static function cleanExpiredSessions(): int
    {
        return SessionRepository::deleteExpiredSessions();
    }

    // Service layer helper: the TTL rule belongs next to session policy so it is not duplicated elsewhere.
    private static function buildExpiryDate(): DateTime
    {
        $expiresAt = new DateTime();
        $expiresAt->add(new DateInterval('P' . self::DEFAULT_TTL_DAYS . 'D'));

        return $expiresAt;
    }

    // Service layer helper: random token generation is part of the auth/session workflow, not persistence.
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    // Service layer helper: hashing belongs here because the service decides the security format for stored tokens.
    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

\class_alias(__NAMESPACE__ . '\\SessionService', 'SessionService');
