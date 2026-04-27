<?php

namespace App\Backend\Services;

use App\Backend\Models\Pool;
use App\Backend\Repositories\PoolRepository;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class PoolService
{
    /**
     * @var string[]
     */
    private const ALLOWED_POOL_TYPE_FILTERS = [
        'pisi',
        'piex',
        'pata',
        'jeud',
    ];

    // Service layer: keeps pool reads behind an application boundary so controllers stay transport-only.
    /**
     * @return Pool[]
     */
    public static function getAllPools(array $filters = []): array
    {
        return PoolRepository::getAllPools(self::normalizePoolFilters($filters));
    }

    // Service layer: ID validation and orchestration are application concerns, not HTTP or SQL concerns.
    public static function getPoolById(int $id): ?Pool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Pool id must be a positive integer.');
        }

        return PoolRepository::getPoolById($id);
    }

    // Service layer: create flow owns payload normalization and model construction rules.
    public static function createPool(array $payload): Pool
    {
        $name = self::requireName($payload);
        $address = self::nullableString($payload, 'address', 'full_address');
        $imageUrl = self::nullableString($payload, 'imageUrl', 'primary_image_url');
        $website = self::nullableString($payload, 'website');
        $map = self::nullableString($payload, 'map', 'map_link');
        $latitude = self::nullableFloat($payload, 'latitude', 'latt');
        $longitude = self::nullableFloat($payload, 'longitude', 'longt');
        $phone = self::nullableString($payload, 'phone');
        $active = self::nullableBool($payload, 'active', 'is_active') ?? true;
        $typeIds = self::normalizeTypeIds($payload['typeIds'] ?? $payload['types'] ?? []);

        $pool = new Pool(
            0,
            $name,
            $address,
            $imageUrl,
            $website,
            $map,
            $latitude,
            $longitude,
            $phone,
            $active,
            new DateTime(),
            $typeIds
        );

        $poolId = PoolRepository::createPool($pool);
        if ($poolId <= 0) {
            throw new RuntimeException('Failed to create pool.');
        }

        $created = PoolRepository::getPoolById($poolId);
        if ($created === null) {
            throw new RuntimeException('Pool was created but could not be loaded.');
        }

        return $created;
    }

    // Service layer: update flow centralizes merge/validation behavior for partial payloads.
    public static function updatePool(int $id, array $payload): ?Pool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Pool id must be a positive integer.');
        }

        $existing = PoolRepository::getPoolById($id);
        if ($existing === null) {
            return null;
        }

        $name = array_key_exists('name', $payload)
            ? self::nonEmptyString((string) $payload['name'], 'name')
            : $existing->getName();

        $address = self::optionalFieldValue($payload, $existing->getAddress(), 'address', 'full_address');
        $imageUrl = self::optionalFieldValue($payload, $existing->getImageUrl(), 'imageUrl', 'primary_image_url');
        $website = self::optionalFieldValue($payload, $existing->getWebsite(), 'website');
        $map = self::optionalFieldValue($payload, $existing->getMap(), 'map', 'map_link');
        $latitude = self::optionalFloatValue($payload, $existing->getLatitude(), 'latitude', 'latt');
        $longitude = self::optionalFloatValue($payload, $existing->getLongitude(), 'longitude', 'longt');
        $phone = self::optionalFieldValue($payload, $existing->getPhone(), 'phone');
        $active = self::optionalBoolValue($payload, $existing->isActive(), 'active', 'is_active');

        $types = array_key_exists('typeIds', $payload) || array_key_exists('types', $payload)
            ? self::normalizeTypeIds($payload['typeIds'] ?? $payload['types'] ?? [])
            : $existing->getTypes();

        $pool = new Pool(
            $id,
            $name,
            $address,
            $imageUrl,
            $website,
            $map,
            $latitude,
            $longitude,
            $phone,
            $active,
            $existing->getCreatedAt(),
            $types
        );

        $updated = PoolRepository::updatePool($pool);
        if (!$updated) {
            return null;
        }

        return PoolRepository::getPoolById($id);
    }

    // Service layer: deletion semantics stay in service to keep controller endpoint-only.
    public static function deletePool(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Pool id must be a positive integer.');
        }

        return PoolRepository::deletePool($id);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requireName(array $payload): string
    {
        if (!array_key_exists('name', $payload)) {
            throw new InvalidArgumentException('name is required.');
        }

        return self::nonEmptyString((string) $payload['name'], 'name');
    }

    private static function nonEmptyString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException($field . ' cannot be empty.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableString(array $payload, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if ($value === null) {
                return null;
            }

            $stringValue = trim((string) $value);
            return $stringValue === '' ? null : $stringValue;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableFloat(array $payload, string ...$keys): ?float
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if ($value === null || $value === '') {
                return null;
            }

            if (!is_numeric($value)) {
                throw new InvalidArgumentException($key . ' must be numeric.');
            }

            return (float) $value;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableBool(array $payload, string ...$keys): ?bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if ($value === null || $value === '') {
                return null;
            }

            return self::toBool($value, $key);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalFieldValue(array $payload, ?string $currentValue, string ...$keys): ?string
    {
        $incoming = self::nullableString($payload, ...$keys);

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $incoming;
            }
        }

        return $currentValue;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalFloatValue(array $payload, ?float $currentValue, string ...$keys): ?float
    {
        $incoming = self::nullableFloat($payload, ...$keys);

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $incoming;
            }
        }

        return $currentValue;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalBoolValue(array $payload, bool $currentValue, string ...$keys): bool
    {
        $incoming = self::nullableBool($payload, ...$keys);

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $incoming ?? $currentValue;
            }
        }

        return $currentValue;
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 0 || $value === 1) {
                return $value === 1;
            }

            throw new InvalidArgumentException($field . ' must be a boolean value.');
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === 'true' || $normalized === '1') {
                return true;
            }

            if ($normalized === 'false' || $normalized === '0') {
                return false;
            }
        }

        throw new InvalidArgumentException($field . ' must be a boolean value.');
    }

    /**
     * @param mixed $rawTypes
     * @return int[]
     */
    private static function normalizeTypeIds($rawTypes): array
    {
        if (!is_array($rawTypes)) {
            throw new InvalidArgumentException('types must be an array of IDs.');
        }

        $typeIds = [];
        foreach ($rawTypes as $rawType) {
            if (is_int($rawType) || (is_string($rawType) && ctype_digit($rawType))) {
                $typeIds[] = (int) $rawType;
                continue;
            }

            throw new InvalidArgumentException('Each type id must be an integer.');
        }

        return array_values(array_unique(array_filter($typeIds, static fn(int $id): bool => $id > 0)));
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{name?: string, types?: string[]}
     */
    private static function normalizePoolFilters(array $filters): array
    {
        $normalized = [];

        if (array_key_exists('name', $filters)) {
            if (!is_string($filters['name'])) {
                throw new InvalidArgumentException('name filter must be a string.');
            }

            $name = trim($filters['name']);
            if ($name !== '') {
                $normalized['name'] = $name;
            }
        }

        if (array_key_exists('types', $filters)) {
            if (!is_array($filters['types'])) {
                throw new InvalidArgumentException('type filter must be an array of names.');
            }

            $types = [];
            foreach ($filters['types'] as $rawType) {
                if (!is_string($rawType)) {
                    throw new InvalidArgumentException('Each type filter must be a string value.');
                }

                $typeName = strtolower(trim($rawType));
                if ($typeName === '') {
                    throw new InvalidArgumentException('Type filter values cannot be empty.');
                }

                if (!in_array($typeName, self::ALLOWED_POOL_TYPE_FILTERS, true)) {
                    throw new InvalidArgumentException(
                        'Invalid type filter. Allowed values: PISI, PIEX, PATA, JEUD. given type: ' . $rawType
                    );
                }

                $types[] = $typeName;
            }

            if ($types !== []) {
                $normalized['types'] = array_values(array_unique($types));
            }
        }

        return $normalized;
    }
}

\class_alias(__NAMESPACE__ . '\\PoolService', 'PoolService');
