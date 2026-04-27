<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Pool;
use App\Backend\Models\PoolType;
use DateTime;
use PDO;
use PDOException;

require_once __DIR__ . '/PoolTypeRepository.php';

/**
 * Repository Layer - PoolRepository
 *
 * Hey Ed, repositories are responsible for ALL database interactions.
 * They should use db/connection.php to access the database.
 * All SQL queries should live here.
 * They return structured PHP arrays or objects to controllers.
 * They do not know anything about HTTP, routing, or JSON responses.
 *
 * Overall request flow:
 * public/index.php → src/backend/api/index.php (router) → controller → repository → database
 */

// Placeholder for PoolRepository class
class PoolRepository
{
    /**
     * @param array{name?: string, types?: string[]} $filters
     */
    public static function getAllPools(array $filters = []): array
    {
        try {
            $conn = \db();
            $sql = "SELECT DISTINCT
                    p.id,
                    p.name,
                    p.full_address,
                    p.primary_image_url,
                    p.website,
                    p.map_link,
                    p.latt,
                    p.longt,
                    p.phone,
                    p.is_active,
                    p.created_at
                 FROM pools p";

            $where = [];
            $params = [];

            if (isset($filters['types']) && $filters['types'] !== []) {
                $sql .= "
                 INNER JOIN pool_pool_types ppt ON ppt.pool_id = p.id
                 INNER JOIN pool_types pt ON pt.id = ppt.pool_type_id";

                $typeConditions = [];
                foreach (array_values($filters['types']) as $index => $typeName) {
                    $placeholder = ':type_' . $index;
                    $typeConditions[] = 'LOWER(pt.description) = ' . $placeholder;
                    $params[$placeholder] = strtolower(trim($typeName));
                }

                $where[] = '(' . implode(' OR ', $typeConditions) . ')';
            }

            if (isset($filters['name']) && $filters['name'] !== '') {
                $where[] = 'LOWER(p.name) LIKE :name';
                $params[':name'] = '%' . strtolower(trim($filters['name'])) . '%';
            }

            if ($where !== []) {
                $sql .= "\n                 WHERE " . implode(' AND ', $where);
            }

            $sql .= "\n                 ORDER BY p.name";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return self::hydratePoolsWithTypes($rows);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function getPoolById(int $id): ?Pool
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT
                    id,
                    name,
                    full_address,
                    primary_image_url,
                    website,
                    map_link,
                    latt,
                    longt,
                    phone,
                    is_active,
                    created_at
                 FROM pools
                 WHERE id = :id"
            );
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            $typesByPoolId = PoolTypeRepository::getTypesByPoolIds([$id]);

            return self::hydratePool($row, $typesByPoolId[$id] ?? []);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function createPool(Pool $pool): int
    {
        $conn = \db();

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                "INSERT INTO pools (
                    name,
                    full_address,
                    primary_image_url,
                    website,
                    map_link,
                    latt,
                    longt,
                    phone,
                    is_active
                ) VALUES (
                    :name,
                    :full_address,
                    :primary_image_url,
                    :website,
                    :map_link,
                    :latt,
                    :longt,
                    :phone,
                    :is_active
                )"
            );
            $stmt->execute([
                ':name' => $pool->getName(),
                ':full_address' => $pool->getAddress(),
                ':primary_image_url' => $pool->getImageUrl(),
                ':website' => $pool->getWebsite(),
                ':map_link' => $pool->getMap(),
                ':latt' => $pool->getLatitude(),
                ':longt' => $pool->getLongitude(),
                ':phone' => $pool->getPhone(),
                ':is_active' => $pool->isActive() ? 1 : 0,
            ]);

            $poolId = (int) $conn->lastInsertId();
            self::syncPoolTypes($conn, $poolId, $pool->getTypes());

            $conn->commit();

            return $poolId;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updatePool(Pool $pool): bool
    {
        $conn = \db();

        if (!self::poolExists($conn, $pool->getId())) {
            return false;
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                "UPDATE pools
                 SET name = :name,
                     full_address = :full_address,
                     primary_image_url = :primary_image_url,
                     website = :website,
                     map_link = :map_link,
                     latt = :latt,
                     longt = :longt,
                     phone = :phone,
                     is_active = :is_active
                 WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $pool->getId(),
                ':name' => $pool->getName(),
                ':full_address' => $pool->getAddress(),
                ':primary_image_url' => $pool->getImageUrl(),
                ':website' => $pool->getWebsite(),
                ':map_link' => $pool->getMap(),
                ':latt' => $pool->getLatitude(),
                ':longt' => $pool->getLongitude(),
                ':phone' => $pool->getPhone(),
                ':is_active' => $pool->isActive() ? 1 : 0,
            ]);

            self::syncPoolTypes($conn, $pool->getId(), $pool->getTypes());

            $conn->commit();

            return true;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public static function deletePool(int $id): bool
    {
        $conn = \db();

        if (!self::poolExists($conn, $id)) {
            return false;
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("DELETE FROM pools WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $conn->commit();

            return true;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return Pool[]
     */
    private static function hydratePoolsWithTypes(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $poolIds = array_map(static fn(array $row): int => (int) $row['id'], $rows);
        $typesByPoolId = PoolTypeRepository::getTypesByPoolIds($poolIds);

        $pools = [];
        foreach ($rows as $row) {
            $poolId = (int) $row['id'];
            $pools[] = self::hydratePool($row, $typesByPoolId[$poolId] ?? []);
        }

        return $pools;
    }

    /**
     * @param array<string, mixed> $row
     * @param PoolType[] $types
     */
    private static function hydratePool(array $row, array $types = []): Pool
    {
        return new Pool(
            (int) $row['id'],
            (string) $row['name'],
            $row['full_address'] !== null ? (string) $row['full_address'] : null,
            $row['primary_image_url'] !== null ? (string) $row['primary_image_url'] : null,
            $row['website'] !== null ? (string) $row['website'] : null,
            $row['map_link'] !== null ? (string) $row['map_link'] : null,
            $row['latt'] !== null ? (float) $row['latt'] : null,
            $row['longt'] !== null ? (float) $row['longt'] : null,
            $row['phone'] !== null ? (string) $row['phone'] : null,
            (bool) $row['is_active'],
            new DateTime((string) $row['created_at']),
            $types
        );
    }

    /**
     * @param PoolType[]|int[] $types
     */
    private static function syncPoolTypes(PDO $conn, int $poolId, array $types): void
    {
        $startedTransaction = false;
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $startedTransaction = true;
        }

        $typeIds = self::normalizeTypeIds($types);

        try {
            $deleteStmt = $conn->prepare("DELETE FROM pool_pool_types WHERE pool_id = :pool_id");
            $deleteStmt->execute([':pool_id' => $poolId]);

            if ($typeIds !== []) {
                $insertStmt = $conn->prepare(
                    "INSERT INTO pool_pool_types (pool_id, pool_type_id)
                     VALUES (:pool_id, :pool_type_id)"
                );

                foreach ($typeIds as $typeId) {
                    $insertStmt->execute([
                        ':pool_id' => $poolId,
                        ':pool_type_id' => $typeId,
                    ]);
                }
            }

            if ($startedTransaction) {
                $conn->commit();
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param PoolType[]|int[] $types
     * @return int[]
     */
    private static function normalizeTypeIds(array $types): array
    {
        $typeIds = [];

        foreach ($types as $type) {
            if ($type instanceof PoolType) {
                $typeIds[] = $type->getId();
                continue;
            }

            if (is_int($type) || ctype_digit((string) $type)) {
                $typeIds[] = (int) $type;
            }
        }

        return array_values(array_unique(array_filter($typeIds, static fn(int $typeId): bool => $typeId > 0)));
    }

    private static function poolExists(PDO $conn, int $id): bool
    {
        $stmt = $conn->prepare("SELECT 1 FROM pools WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }
}

\class_alias(__NAMESPACE__ . '\\PoolRepository', 'PoolRepository');
