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
    public function indexTypes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $types = PoolTypeRepository::getAllTypes();
        $this->jsonResponse([
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
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $pools = PoolService::getAllPools();
        $this->jsonResponse([
            'pools' => array_map(fn(Pool $pool): array => $this->formatPool($pool), $pools),
        ]);
    }

    // Controller layer: ID parsing and response status mapping are transport responsibilities.
    public function show(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::getPoolById($id);
            if ($pool === null) {
                $this->jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            $this->jsonResponse($this->formatPool($pool));
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Controller layer: request payload extraction is an HTTP concern; service handles domain validation.
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::createPool($this->readJsonBody());
            $this->jsonResponse($this->formatPool($pool), 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['error' => 'Failed to create pool.'], 500);
        }
    }

    // Controller layer: maps HTTP method/status while delegating update business logic to service.
    public function update(int $id): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method !== 'PUT' && $method !== 'PATCH') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $pool = PoolService::updatePool($id, $this->readJsonBody());
            if ($pool === null) {
                $this->jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            $this->jsonResponse($this->formatPool($pool));
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['error' => 'Failed to update pool.'], 500);
        }
    }

    // Controller layer: delete endpoint status mapping belongs to transport handling.
    public function destroy(int $id): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'DELETE') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $deleted = PoolService::deletePool($id);
            if (!$deleted) {
                $this->jsonResponse(['error' => 'Pool not found.'], 404);
                return;
            }

            $this->jsonResponse(['ok' => true]);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Controller helper: request body parsing stays in controller because it is transport shaping logic.
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

    // Controller helper: response shaping is a transport concern and should stay out of services.
    /**
     * @return array<string, mixed>
     */
    private function formatPool(Pool $pool): array
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
    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}

\class_alias(__NAMESPACE__ . '\\PoolController', 'PoolController');