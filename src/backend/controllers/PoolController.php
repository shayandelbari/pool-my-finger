<?php

namespace App\Backend\Controllers;

use App\Backend\Models\Pool;
use App\Backend\Models\PoolType;
use App\Backend\Repositories\PoolTypeRepository;
use App\Backend\Services\PoolService;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

class PoolController
{
    public static function indexTypes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $types = PoolTypeRepository::getAllTypes();
        self::jsonResponse([
            'types' => array_map(
                fn(PoolType $type): array => [
                    'id' => $type->getId(),
                    'name' => $type->getName(),
                    'description' => $type->getDescription(),
                ],
                $types
            ),
        ]);
    }

    // Controller layer: this endpoint only handles HTTP concerns and delegates pool listing to the service.
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pools = PoolService::getAllPools(self::readPoolFilters());
            self::jsonResponse([
                'pools' => array_map(fn(Pool $pool): array => self::formatPool($pool), $pools),
            ]);
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Controller layer: ID parsing and response status mapping are transport responsibilities.
    public static function show(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::getPoolById($id);
            if ($pool === null) {
                self::jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            self::jsonResponse(self::formatPool($pool));
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Controller layer: request payload extraction is an HTTP concern; service handles domain validation.
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::createPool(self::readJsonBody());
            self::jsonResponse(self::formatPool($pool), 201);
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            self::jsonResponse(['error' => 'Failed to create pool.'], 500);
        }
    }

    // Controller layer: maps HTTP method/status while delegating update business logic to service.
    public static function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method !== 'PUT' && $method !== 'PATCH') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::updatePool($id, self::readJsonBody());
            if ($pool === null) {
                self::jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            self::jsonResponse(self::formatPool($pool));
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            self::jsonResponse(['error' => 'Failed to update pool.'], 500);
        }
    }

    // Controller layer: delete endpoint status mapping belongs to transport handling.
    public static function destroy(int $id): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'DELETE') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $deleted = PoolService::deletePool($id);
            if (!$deleted) {
                self::jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            self::jsonResponse(['ok' => true]);
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Controller helper: request body parsing stays in controller because it is transport shaping logic.
    /**
     * @return array<string, mixed>
     */
    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    /**
     * @return array{name?: string, types?: string[]}
     */
    private static function readPoolFilters(): array
    {
        $filters = [];

        if (array_key_exists('name', $_GET)) {
            $rawName = $_GET['name'];
            if (!is_string($rawName)) {
                throw new InvalidArgumentException('name filter must be a string.');
            }

            $name = trim($rawName);
            if ($name !== '') {
                $filters['name'] = $name;
            }
        }

        if (array_key_exists('type', $_GET)) {
            $rawType = $_GET['type'];
            if (!is_string($rawType)) {
                throw new InvalidArgumentException('type filter must be a comma-separated string.');
            }

            $rawType = trim($rawType);
            if ($rawType === '') {
                throw new InvalidArgumentException('type filter cannot be empty when provided.');
            }

            $parts = explode(',', $rawType);
            $types = [];
            foreach ($parts as $part) {
                $typeName = trim($part);
                if ($typeName === '') {
                    throw new InvalidArgumentException('type filter contains an empty value.');
                }

                $types[] = $typeName;
            }

            $filters['types'] = $types;
        }

        return $filters;
    }

    // Controller helper: response shaping is a transport concern and should stay out of services.
    /**
     * @return array<string, mixed>
     */
    private static function formatPool(Pool $pool): array
    {
        return [
            'id' => $pool->getId(),
            'name' => $pool->getName(),
            'address' => $pool->getAddress(),
            'imageUrl' => $pool->getImageUrl(),
            'website' => $pool->getWebsite(),
            'map' => $pool->getMap(),
            'latitude' => $pool->getLatitude(),
            'longitude' => $pool->getLongitude(),
            'phone' => $pool->getPhone(),
            'active' => $pool->isActive(),
            'createdAt' => $pool->getCreatedAt()->format(DateTimeInterface::ATOM),
            'types' => array_map(
                fn(PoolType $type): array => [
                    'id' => $type->getId(),
                    'name' => $type->getName(),
                    'description' => $type->getDescription(),
                ],
                $pool->getTypes()
            ),
        ];
    }

    // Controller helper: emitting HTTP status and JSON payload is endpoint-layer behavior.
    /**
     * @param array<string, mixed> $payload
     */
    private static function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}

\class_alias(__NAMESPACE__ . '\\PoolController', 'PoolController');