<?php

namespace App\Backend\Repositories;

use App\Backend\Models\ScheduleType;
use DateTime;
use PDO;
use PDOException;

class ScheduleTypeRepository {
  public static function getAllScheduleTypes(): array
  {
    $array = [];
    $counter = 0;
    try {
      $conn = \db();
      $sql = "SELECT * FROM schedule_types";
      $stmt = $conn->query($sql);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($data as &$row) {
        $scheduleType = new ScheduleType(
          $row['id'],
          $row['name'],
          $row['description']
        );
        $array[$counter++] = $scheduleType;
      }
      return $array;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return []; 
    }
  }

  public static function getScheduleTypesById(int $id): ?ScheduleType
  {
    try {
      $conn = \db();
      $sql = "SELECT * FROM schedule_types WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $scheduleTypeArr = $stmt->fetch(PDO::FETCH_ASSOC);

      if($scheduleTypeArr !== false) {
        return new ScheduleType(
          $scheduleTypeArr['id'],
          $scheduleTypeArr['name'],
          $scheduleTypeArr['description']
        );
      }

      return null;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null; 
    }
  } 

  public static function createScheduleType(ScheduleType $newSchedule): int
  {
    try {
      $conn = \db();
      $sql = "INSERT INTO schedule_types (name, description) VALUES (:name, :description)";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':name', $newSchedule->getName());
      $stmt->bindParam(':description', $newSchedule->getDescription());
      $stmt->execute();

      return $conn->lastInsertId(); 

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return -1;
    }
  }

  public static function updateScheduleType(int $id, ScheduleType $updatedSchedule): bool
  {
    try {
      $conn = \db();
      $sql = "UPDATE schedule_types SET name = :name, description = :description WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->bindParam(':name', $updatedSchedule->getName());
      $stmt->bindParam(':description', $updatedSchedule->getDescription());
      $stmt->execute();

      return $stmt->rowCount() > 0;

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return false;
    }
  }

  public static function deleteScheduleType(int $id): bool
  {
    try {
      $conn = \db();
      $sql = "DELETE FROM schedule_types WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      return $stmt->execute();
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return false;
    }
  }


}
