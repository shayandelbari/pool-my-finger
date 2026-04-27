<?php

namespace App\Backend\Repositories;

use App\Backend\Models\PoolType;
use PDO;
use PDOException;

class PoolTypeRepository
{
    /**
     * @return PoolType[]
     */
    public static function getAllTypes(): array
    {
        try {
            $conn = \db();
            $stmt = $conn->query(
                "SELECT id, name, description
                 FROM pool_types
                 ORDER BY name"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(fn(array $row): PoolType => self::hydratePoolType($row), $rows);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public static function getTypeById(int $id): ?PoolType
    {
        try {
            $conn = \db();
            $stmt = $conn->prepare(
                "SELECT id, name, description
                 FROM pool_types
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return self::hydratePoolType($row);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public static function createType(PoolType $type): int
    {
        $conn = \db();
        $stmt = $conn->prepare(
            "INSERT INTO pool_types (name, description)
             VALUES (:name, :description)"
        );
        $stmt->execute([
            ':name' => $type->getName(),
            ':description' => $type->getDescription(),
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function updateType(int $id, PoolType $type): bool
    {
        $conn = \db();
        try {
            $stmt = $conn->prepare(
            "UPDATE pool_types
             SET name = :name,
                 description = :description
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id,
            ':name' => $type->getName(),
            ':description' => $type->getDescription(),
        ]);

        return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public static function deleteType(int $id): bool
    {
        $conn = \db();
        $stmt = $conn->prepare("DELETE FROM pool_types WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int[] $poolIds
     * @return array<int, PoolType[]>
     */
    public static function getTypesByPoolIds(array $poolIds): array
    {
        if ($poolIds === []) {
            return [];
        }

        $conn = \db();
        $placeholders = implode(',', array_fill(0, count($poolIds), '?'));
        $stmt = $conn->prepare(
            "SELECT
                ppt.pool_id,
                pt.id,
                pt.name,
                pt.description
             FROM pool_pool_types ppt
             INNER JOIN pool_types pt ON pt.id = ppt.pool_type_id
             WHERE ppt.pool_id IN ({$placeholders})
             ORDER BY pt.name"
        );
        $stmt->execute(array_values($poolIds));

        $typesByPoolId = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $poolId = (int) $row['pool_id'];
            $typesByPoolId[$poolId] ??= [];
            $typesByPoolId[$poolId][] = self::hydratePoolType($row);
        }

        return $typesByPoolId;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hydratePoolType(array $row): PoolType
    {
        return new PoolType(
            (int) $row['id'],
            (string) $row['name'],
            $row['description'] !== null ? (string) $row['description'] : null
        );
    }
}

\class_alias(__NAMESPACE__ . '\\PoolTypeRepository', 'PoolTypeRepository');