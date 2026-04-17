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

    // Controller layer: this method only translates the HTTP login request into a service call and formats the response.
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

    // Controller layer: validation is an endpoint concern because it reads transport state and returns an HTTP response.
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

    // Controller layer: logout is HTTP orchestration only; the service owns the revocation rule itself.
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

    // Controller layer: bulk logout is still an endpoint wrapper around a service-level user-session action.
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

    // Controller layer: this endpoint returns the current authenticated user, so it belongs here as transport handling.
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

    // Controller helper: cookie access is HTTP transport logic, so it stays out of services.
    private function resolveToken(): ?string
    {
        $cookie = $_COOKIE[self::SESSION_COOKIE_NAME] ?? null;

        if (!is_string($cookie) || trim($cookie) === '') {
            return null;
        }

        return trim($cookie);
    }

    // Controller helper: body parsing is request-shaping logic, not business logic.
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

    // Controller helper: response shaping is an endpoint responsibility because services should not know response format.
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

    // Controller helper: session shaping is response formatting, which belongs in the controller layer.
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

    // Controller helper: HTTP status/header emission belongs here because only controllers should speak HTTP directly.
    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    // Controller helper: cookie mutation is transport/state management, so it stays in the controller.
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

    // Controller helper: clearing the cookie is endpoint cleanup, not application logic.
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

    // Controller helper: protocol detection is needed for cookie flags and is still an HTTP-layer concern.
    private function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}

\class_alias(__NAMESPACE__ . '\\AuthController', 'AuthController');
