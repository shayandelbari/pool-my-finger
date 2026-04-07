<?php
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
    public function getAllPools(): array
    {
        $array = [];
        try {

            $conn = db();
            $sql = "SELECT ";
            $result = $conn->query($sql);
            $data = $result->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $pool = new Pool(
                    $row['id'],
                    $row['name'],
                    $row['full_address'],
                    $row['primary_image_url'],
                    $row['website'],
                    $row['map_link'],
                    (float) $row['latt'],
                    (float) $row['longt'],
                    (float) $row['phone'],
                    $row['type'],
                    (bool) $row['is_active'],
                    new DateTime($row['created_at'])
                );
                $array[] = $pool;
            }

            return $array;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function getPoolById(int $id): ?array
    {
        try {
            $conn = db();
            $sql = "SELECT * FROM pools WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $pool = $stmt->fetch(PDO::FETCH_ASSOC);
            return $pool ?: null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
}