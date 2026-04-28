<?php

namespace App\Backend\Repositories;

use PDO;
use PDOException;

require_once __DIR__ . '/PoolTypeRepository.php';

class TopPoolsRepository
{
    /**
     * @param string[] $types
     * @return array<int, array<string,mixed>>
     */
    public static function findNearbyPools(float $minLat, float $maxLat, float $minLng, float $maxLng, array $types = []): array
    {
        try {
            $conn = \db();

            $sql = "SELECT
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
                        p.created_at,
                        p.id AS pool_id
                    FROM pools p";

            $params = [
                ':minLat' => $minLat,
                ':maxLat' => $maxLat,
                ':minLng' => $minLng,
                ':maxLng' => $maxLng,
            ];

            if ($types !== []) {
                $sql .= "
                    INNER JOIN pool_pool_types ppt ON ppt.pool_id = p.id
                    INNER JOIN pool_types pt ON pt.id = ppt.pool_type_id";
            }

            $sql .= "
                WHERE p.is_active = 1
                  AND p.latt BETWEEN :minLat AND :maxLat
                  AND p.longt BETWEEN :minLng AND :maxLng";

            if ($types !== []) {
                $typeConditions = [];
                foreach (array_values($types) as $index => $typeName) {
                    $placeholder = ':type_' . $index;
                    $typeConditions[] = 'LOWER(pt.description) = ' . $placeholder;
                    $params[$placeholder] = strtolower(trim($typeName));
                }

                $sql .= "\n                  AND (" . implode(' OR ', $typeConditions) . ')';
            }

            $sql .= "\n                GROUP BY p.id
                ORDER BY p.name";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows ?: [];
        } catch (PDOException $e) {
            error_log('TopPoolsRepository DB error: ' . $e->getMessage());
            return [];
        }
    }
}

\class_alias(__NAMESPACE__ . '\\TopPoolsRepository', 'TopPoolsRepository');
