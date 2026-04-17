<?php

namespace App\Backend\Controllers;

use App\Backend\Models\Session;
use App\Backend\Models\User;
use App\Backend\Services\Exceptions\InvalidCredentialsException;
use App\Backend\Services\Exceptions\SessionValidationException;
use App\Backend\Services\SessionService;
use App\Backend\Services\UserService;
use DateTimeInterface;

class AuthController
{
    private const SESSION_COOKIE_NAME = 'pool_my_finger_session';

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $body = $this->readJsonBody();
        $username = isset($body['username']) ? trim((string) $body['username']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';

        if ($username === '' || $password === '') {
            $this->jsonResponse(['error' => 'username and password are required.'], 400);
            return;
        }

        try {
            $result = UserService::login($username, $password);
            $this->setSessionCookie($result['token'], $result['expiresAt']);

            $this->jsonResponse([
                'user' => $this->formatUser($result['user']),
                'expiresAt' => $result['expiresAt']->format(DateTimeInterface::ATOM),
            ]);
        } catch (InvalidCredentialsException $e) {
            $this->jsonResponse(['error' => 'Invalid username or password.'], 401);
        }
    }

    public function validate(): void
    {
        $token = $this->resolveToken();
        if ($token === null) {
            $this->jsonResponse(['error' => 'Missing session cookie.'], 401);
            return;
        }

        try {
            $session = SessionService::validateSessionToken($token);
            $this->jsonResponse($this->formatSession($session));
        } catch (SessionValidationException $e) {
            $this->jsonResponse(['error' => 'Session invalid.'], 401);
        }
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $token = $this->resolveToken();
        if ($token === null) {
            $this->jsonResponse(['error' => 'Missing session cookie.'], 401);
            return;
        }

        $revoked = SessionService::logoutCurrentSession($token);
        if (!$revoked) {
            $this->jsonResponse(['error' => 'Session not found.'], 404);
            return;
        }

        $this->clearSessionCookie();
        $this->jsonResponse(['ok' => true]);
    }

    public function logoutAll(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $token = $this->resolveToken();
        if ($token === null) {
            $this->jsonResponse(['error' => 'Missing session cookie.'], 401);
            return;
        }

        try {
            $session = SessionService::validateSessionToken($token);
            $count = SessionService::logoutAllUserSessions($session->getUser()->getId());

            $this->jsonResponse(['revokedCount' => $count]);
        } catch (SessionValidationException $e) {
            $this->jsonResponse(['error' => 'Session invalid.'], 401);
        }
    }

    public function getUserById(): void
    {
        $token = $this->resolveToken();
        if ($token === null) {
            $this->jsonResponse(['error' => 'Missing session cookie.'], 401);
            return;
        }

        try {
            $session = SessionService::validateSessionToken($token);
            $this->jsonResponse($this->formatUser($session->getUser()));
        } catch (SessionValidationException $e) {
            $this->jsonResponse(['error' => 'Session invalid.'], 401);
            return;
        }
    }

    private function resolveToken(): ?string
    {
        $cookie = $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;

        if (!is_string($cookie) || trim($cookie) === '') {
            return null;
        }

        return trim($cookie);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'createdAt' => $user->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSession(Session $session): array
    {
        return [
            'id' => $session->getId(),
            'user' => $this->formatUser($session->getUser()),
            'expiresAt' => $session->getExpires()->format(DateTimeInterface::ATOM),
            'revoked' => $session->isRevoked(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function setSessionCookie(string $token, DateTimeInterface $expiresAt): void
    {
        setcookie(self::SESSION_COOKIE_NAME, $token, [
            'expires' => $expiresAt->getTimestamp(),
            'path' => defined('BASE_URL') ? BASE_URL : '/',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearSessionCookie(): void
    {
        setcookie(self::SESSION_COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => defined('BASE_URL') ? BASE_URL : '/',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}

\class_alias(__NAMESPACE__ . '\\AuthController', 'AuthController');
