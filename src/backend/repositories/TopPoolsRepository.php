<?php

namespace App\Backend\Repositories;

use PDO;
use PDOException;

require_once __DIR__ . '/PoolTypeRepository.php';

class TopPoolsRepository
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public static function findCandidates(float $minLat, float $maxLat, float $minLng, float $maxLng, string $date, string $time, ?string $type = null): array
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
                        s.id AS schedule_id,
                        s.effective_date,
                        s.end_date,
                        s.day_of_week,
                        tb.id AS time_block_id,
                        tb.start_time,
                        tb.end_time,
                        tb.label
                    FROM pools p
                    INNER JOIN schedules s ON s.pool_id = p.id
                    INNER JOIN time_blocks tb ON tb.schedule_id = s.id
                    WHERE p.is_active = 1
                      AND p.latt BETWEEN :minLat AND :maxLat
                      AND p.longt BETWEEN :minLng AND :maxLng
                      AND s.effective_date <= :date
                      AND (s.end_date IS NULL OR s.end_date >= :date)
                      AND s.day_of_week = :dow";

            $params = [
                ':minLat' => $minLat,
                ':maxLat' => $maxLat,
                ':minLng' => $minLng,
                ':maxLng' => $maxLng,
                ':date' => $date,
                ':dow' => date('w', strtotime($date)),
            ];

            if ($type !== null && $type !== '') {
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
                            s.id AS schedule_id,
                            s.effective_date,
                            s.end_date,
                            s.day_of_week,
                            tb.id AS time_block_id,
                            tb.start_time,
                            tb.end_time,
                            tb.label
                        FROM pools p
                        INNER JOIN pool_pool_types ppt ON ppt.pool_id = p.id
                        INNER JOIN pool_types pt ON pt.id = ppt.pool_type_id
                        INNER JOIN schedules s ON s.pool_id = p.id
                        INNER JOIN time_blocks tb ON tb.schedule_id = s.id
                        WHERE p.is_active = 1
                          AND p.latt BETWEEN :minLat AND :maxLat
                          AND p.longt BETWEEN :minLng AND :maxLng
                          AND LOWER(pt.description) = :type
                          AND s.effective_date <= :date
                          AND (s.end_date IS NULL OR s.end_date >= :date)
                          AND s.day_of_week = :dow";

                $params[':type'] = $type;
            }

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
