<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Schedule;
use DateTime;
use PDO;
use PDOException;

require_once '/../models/Schedule.php';

class ScheduleRepository {
  public static function getAllSchedules(): array
    {
        $array = [];
        $counter = 0;
        $conn = \db();
        try {
            $sql = "SELECT * FROM schedules";
            $result = $conn->query($sql);
            $data = $result->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $schedule = new Schedule(
                    $row['id'],
                    $row['pool_id'],
                    $row['schedule_type_id'],
                    $row['effective_date'],
                    $row['end_date'],
                    new DateTime($row['created_at'])
                );
                $array[$counter++] = $schedule;
            }

            return $array;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
  public static function getScheduleById(int $id): ?Schedule 
  {
    $conn = \db();
    try {
      $sql = "SELECT * FROM schedules WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $scheduleArr = $stmt->fetch(PDO::FETCH_ASSOC); //array is the schedule

      if ($scheduleArr !== false) {
        return new Schedule(
          $scheduleArr['id'],
          $scheduleArr['pool_id'],
          $scheduleArr['schedule_type_id'],
          $scheduleArr['effective_date'],
          $scheduleArr['end_date'],
          new DateTime($scheduleArr['created_at'])
        );
      }
      return null;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null;
    }
  }

  public static function getScheduleByPoolId(int $poolId): array {
    $conn = \db();
    try {
      $sql = "SELECT * FROM schedules WHERE pool_id = :pool_id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':pool_id', $poolId, PDO::PARAM_INT);
      $stmt->execute();
      $schedulesArr = $stmt->fetchAll(PDO::FETCH_ASSOC); //array of schedules

      $schedules = [];
      foreach ($schedulesArr as $oneScheduleArr) {
        $schedules[] = new Schedule(
          $oneScheduleArr['id'],
          $oneScheduleArr['pool_id'],
          $oneScheduleArr['schedule_type_id'],
          $oneScheduleArr['effective_date'],
          $oneScheduleArr['end_date'],
          new DateTime($oneScheduleArr['created_at'])
        );
      }
      return $schedules;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return [];
    }
    
  }

  public static function createSchedule(Schedule $schedule): int
  {
    $conn =  \db();
    try {
      $conn->beginTransaction();  
      $sql = "INSERT INTO schedules (schedule_type_id, effective_date, end_date, created_at) VALUES (:pool_id, :schedule_type_id, :effective_date, :end_date, :created_at)";

      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':schedule_type_id', $schedule->getType());
      $stmt->bindParam(':effective_date', $schedule->getEffectiveDate());
      $stmt->bindParam(':end_date', $schedule->getEndDate());
      $stmt->bindParam(':created_at', $schedule->getCreatedAt()); //Q:not include it because of type DateTime?
      $stmt->execute();
      $conn->commit();

      //get theid of the newly created schedule
      return (int) $conn->lastInsertId();

      // //get the pool created from the db
      // return $this->getScheduleById($scheduleId);

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return -1;
    }
  }

  public static function updateSchedule(int $oldId, Schedule $newSchedule) : bool
  {
    $conn = \db();

    if (!self::scheduleExistsById($oldId)) {
        error_log("Schedule with ID " .$oldId. " does not exist.");
        return false; 
    }
    
    try {
      $sql = "UPDATE schedules SET id = :id, pool_id = :pool_id, schedule_type_id = :schedule_type_id, effective_date = :effective_date, end_date = :end_date, created_at = :created_at WHERE id = :oldId";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $oldId);
      $stmt->bindParam(':pool_id', $newSchedule->getPool()->getId());
      $stmt->bindParam(':schedule_type_id', $newSchedule->getType());
      $stmt->bindParam(':effective_date', $newSchedule->getEffectiveDate());
      $stmt->bindParam(':end_date', $newSchedule->getEndDate());
      $stmt->bindParam(':created_at', $newSchedule->getCreatedAt());
      $stmt->execute();

      return true;

    } catch (PDOException $e) {
      error_log("Database error: ". $e->getMessage());
      return false;
    }
  }

  private static function scheduleExistsById(int $id): bool
  {
      $conn = \db();
      try {
        $stmt = $conn->prepare("SELECT 1 FROM schedules WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
      } catch (PDOException $e) {
          error_log("Database error: " . $e->getMessage());
          return false;
      }
  }
  

  public static function getScheduleIdByFields(int $poolId, int $scheduleTypeId, string $effectiveDate, string $endDate): int {
    $conn = \db();
    try {
      $sql = "
      SELECT id
      FROM schedules
      WHERE pool_id = :pool_id
        AND schedule_type_id = :schedule_type_id
        AND effective_date = :effective_date
        AND end_date = :end_date
      LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
      ':pool_id' => $poolId,
      ':schedule_type_id' => $scheduleTypeId,
      ':effective_date' => $effectiveDate,
      ':end_date' => $endDate,
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? (int) $result['id'] : null;
    } catch (PDOException $e) {
      error_log("Database error: ".$e->getMessage());
      return -1;
    }
  }

  public static function deleteSchedule(int $scheduleId): bool 
  {
    try {
      $conn = \db();
      $sql = "DELETE FROM schedules WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $scheduleId);
      return $stmt->execute();
    }
    catch (PDOException $e) {
      error_log("Database error: ".$e->getMessage());
      return false;
    }
  }

}


\class_alias(__NAMESPACE__ . '\\ScheduleRepository', 'ScheduleRepository');


