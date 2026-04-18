<?php

namespace App\Backend\Services;

use App\Backend\Models\User;
use App\Backend\Repositories\UserRepository;
use App\Backend\Services\Exceptions\InvalidCredentialsException;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class UserService
{
    // Service layer: owns user creation policy because hashing and validation are reusable business rules, not HTTP concerns.
    /**
     * Creates a user with hashed password.
     * This is intentionally service-only and not exposed as an API endpoint.
     */
    public static function createUser(string $username, string $plainPassword): int
    {
        $username = trim($username);
        if ($username === '') {
            throw new InvalidArgumentException('Username is required.');
        }

        if ($plainPassword === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Failed to hash password.');
        }

        $userId = UserRepository::createUser($username, $passwordHash);
        if ($userId <= 0) {
            throw new RuntimeException('Failed to create user.');
        }

        return $userId;
    }

    // Service layer: owns login orchestration because it coordinates repository checks and session creation in one application use case.
    /**
     * @return array{user:User, sessionId:int, token:string, expiresAt:DateTime}
     * @throws InvalidCredentialsException
     */
    public static function login(string $username, string $plainPassword): array
    {
        $user = UserRepository::verifyCredentials($username, $plainPassword);
        if ($user === null) {
            throw new InvalidCredentialsException('Invalid username or password.');
        }

        $sessionData = SessionService::createSession($user);

        return [
            'user' => $user,
            'sessionId' => $sessionData['sessionId'],
            'token' => $sessionData['token'],
            'expiresAt' => $sessionData['expiresAt'],
        ];
    }

    // Service layer: keeps user reads behind the application boundary so controllers stay endpoint-only.
    public static function getUserById(int $id): ?User
    {
        return UserRepository::getUserById($id);
    }
}

\class_alias(__NAMESPACE__ . '\\UserService', 'UserService');
