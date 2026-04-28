<?php

namespace App\Backend\Controllers;

use App\Backend\Models\Pool;
use App\Backend\Services\TopPoolsService;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class TopPoolsController
{
    public static function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $postalCode = $_GET['postalCode'] ?? null;
            $distance = $_GET['distance'] ?? ($_GET['radius'] ?? null);
            $dateTimeRaw = $_GET['time'] ?? ($_GET['dateTime'] ?? null);
            $type = $_GET['type'] ?? null;

            if (!is_string($postalCode) || trim($postalCode) === '') {
                throw new InvalidArgumentException('postalCode is required.');
            }

            if ($distance === null || !is_numeric($distance)) {
                throw new InvalidArgumentException('distance is required and must be numeric.');
            }

            $distanceFloat = (float) $distance;

            if ($dateTimeRaw !== null && $dateTimeRaw !== '') {
                $dateTime = new DateTimeImmutable($dateTimeRaw);
            } else {
                $dateTime = new DateTimeImmutable();
            }

            $types = self::readTypesFilter($type);

            $results = TopPoolsService::findTopPools((string) $postalCode, $distanceFloat, $dateTime, $types);

            $payload = ['pools' => array_map(
                static fn(array $result): array => self::formatPoolSearchResult(
                    $result['pool'],
                    $result['relevance'] ?? null,
                    $result['distance'] ?? null
                ),
                $results
            )];
            self::jsonResponse($payload);
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('TopPoolsController error: ' . $e->getMessage());
            self::jsonResponse(['error' => 'Internal server error.'], 500);
        }
    }

    /**
     * @param mixed $rawType
     * @return string[]
     */
    private static function readTypesFilter($rawType): array
    {
        if ($rawType === null) {
            return [];
        }

        if (!is_string($rawType)) {
            throw new InvalidArgumentException('type filter must be a comma-separated string.');
        }

        $trimmedType = trim($rawType);
        if ($trimmedType === '') {
            return [];
        }

        $types = [];
        foreach (explode(',', $trimmedType) as $part) {
            $typeName = strtolower(trim($part));
            if ($typeName === '') {
                throw new InvalidArgumentException('type filter contains an empty value.');
            }

            $types[] = $typeName;
        }

        return array_values(array_unique($types));
    }

    /**
     * @param array<string, mixed>|null $relevance
     */
    private static function formatPoolSearchResult(Pool $pool, ?array $relevance, ?float $distance = null): array
    {
        $payload = [
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
                static fn($type): array => [
                    'id' => $type->getId(),
                    'name' => $type->getName(),
                    'description' => $type->getDescription(),
                ],
                $pool->getTypes()
            ),
            'relevance' => $relevance,
        ];

        if ($distance !== null) {
            $payload['distance'] = round($distance, 2);
            $payload['distanceChipText'] = 'Distance: ' . number_format($distance, 1) . ' km';
        }

        return $payload;
    }

    /** @param array<string,mixed> $payload */
    private static function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}

\class_alias(__NAMESPACE__ . '\\TopPoolsController', 'TopPoolsController');
