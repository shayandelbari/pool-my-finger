<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Pool;
use DateTime;
use PDO;
use PDOException;

require_once '../models/Pool.php';
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

            $conn = \db();
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
                    $row['phone'] !== null ? (string) $row['phone'] : null,
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

    public function getPoolById(int $id): ?Pool // declaring the type of your prameters and output of the function
    {
        try {
            $conn = \db();
            $sql = "SELECT * FROM pools WHERE id = :id";
            $stmt = $conn->prepare($sql); //have the query ready for execution
            $stmt->bindParam(':id', $id, PDO::PARAM_INT); //placeholder, parameter, typpe of parameter
            $stmt->execute(); 
            $pool = $stmt->fetch(PDO::FETCH_ASSOC); //get the result as an associative array
            return $pool ?: null;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    //return the object
    public function createPool(Pool $newPool): ?Pool  
    {
        try {
            $conn = db();
            $sql = "INSERT INTO pools (name, full_address, primary_image_url, website, map_link, latt, longt, phone, type, is_active) VALUES (:name, :full_address, :primary_image_url, :website, :map_link, :latt, :longt, :phone, :type, :is_active)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $newPool->getName());
            $stmt->bindParam(':full_address', $newPool->getAddress());
            $stmt->bindParam(':primary_image_url', $newPool->getImageUrl());
            $stmt->bindParam(':website', $newPool->getWebsite());
            $stmt->bindParam(':map_link', $newPool->getMap());
            $stmt->bindParam(':latt', $newPool->getLattitude());
            $stmt->bindParam(':longt', $newPool->getLongitude());
            $stmt->bindParam(':phone', $newPool->getPhone());
            $stmt->bindParam(':type', $newPool->getType());
            $stmt->bindParam(':is_active', $newPool->isActive(), PDO::PARAM_BOOL);
            $stmt->execute();
            
            // Get the ID of the newly inserted pool
            $newPoolId = (int)$conn->lastInsertId();
            
            //get the pool created from the db 
            return $this->getPoolById($newPoolId);

        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    //return object for update
    public function updatePool(int $oldId, Pool $newPool): ?Pool 
    {
        try {
            $conn = db(); 
            $sqlQuery = "UPDATE pools SET (:name, :full_address, :primary_image_url, :website, :map_link, :latt, :longt, :phone, :type, :is_active) WHERE id = :oldId";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $newPool->getName());
            $stmt->bindParam(':full_address', $newPool->getAddress());
            $stmt->bindParam(':primary_image_url', $newPool->getImageUrl());
            $stmt->bindParam(':website', $newPool->getWebsite());
            $stmt->bindParam(':map_link', $newPool->getMap());
            $stmt->bindParam(':latt', $newPool->getLattitude());
            $stmt->bindParam(':longt', $newPool->getLongitude());
            $stmt->bindParam(':phone', $newPool->getPhone());
            $stmt->bindParam(':type', $newPool->getType());
            $stmt->bindParam(':is_active', $newPool->isActive(), PDO::PARAM_BOOL);
            $stmt->execute();

        } catch(PDOException $e) {
            error_log("Database error: ". $e->getMessage());
            return null;
        }
    }

    //method to deactive and activate the is_active field

}

\class_alias(__NAMESPACE__ . '\\PoolRepository', 'PoolRepository');