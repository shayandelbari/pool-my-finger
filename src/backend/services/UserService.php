<?php

namespace App\Backend\Services;

use App\Backend\Models\User;
use App\Backend\Repositories\UserRepository;
use App\Backend\Services\Exceptions\InvalidCredentialsException;
use DateTime;

class UserService
{
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

    public static function getUserById(int $id): ?User
    {
        return UserRepository::getUserById($id);
    }
}

\class_alias(__NAMESPACE__ . '\\UserService', 'UserService');
