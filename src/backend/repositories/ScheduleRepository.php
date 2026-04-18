<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Schedule;
use DateTime;
use PDO;
use PDOException;

require_once '../models/Schedule.php';

class ScheduleRepository {
  public function getAllSchedules(): array
    {
        $array = [];
        try {

            $conn = \db();
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
                $array[] = $schedule;
            }

            return $array;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
  public function getScheduleById(int $id): ?Schedule 
  {
    try {
      $conn = \db();
      $sql = "SELECT * FROM schedules WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
      return $schedule ?: null;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null;
    }
  }

  public function createSchedule(Schedule $schedule): ?Schedule 
  {
    try {
      $conn =  \db(); //Q: what means the \db();
      //Q: no schedule_id because it is auto incremented??
      $sql = "INSERT INTO schedules (pool_id, schedule_type_id, effective_date, end_date, created_at) VALUES (:pool_id, :schedule_type_id, :effective_date, :end_date, :created_at)";

      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':pool_id', $schedule->getPool());
      $stmt->bindParam(':schedule_type_id', $schedule->getType());
      $stmt->bindParam(':effective_date', $schedule->getEffectiveDate());
      $stmt->bindParam(':end_date', $schedule->getEndDate());
      $stmt->bindParam(':created_at', $schedule->getCreatedAt()); //Q:not include it because of type DateTime?
      $stmt->execute();

      //get theid of the newly created schedule
      $scheduleId = $conn->lastInsertId();

      //get the pool created from the db
      return $this->getScheduleById($scheduleId);

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null;
    }
  }

  public function updateSchedule(int $oldId, Schedule $newSchedule) :? Schedule
  {
    try {
      $conn = db();
      $sql = "UPDATE schedules SET (:id, :pool_id, :schedule_type, :effective_date, :endDate, :created_at) WHERE id = :oldId";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $newSchedule->getId());
      $stmt->bindParam(':pool_id', $newSchedule->getPoolId());
      $stmt->bindParam(':schedule_type', $newSchedule->getType());
      $stmt->bindParam(':effective_date', $newSchedule->getEffectiveDate());
      $stmt->bindParam(':endDate', $newSchedule->getEndDate());
      $stmt->execute();

    } catch (PDOException $e) {
      error_log("Database error: ". $e->getMessage());
      return null;
    }
  }

  public function deleteSchedule(int $scheduleId): bool {
    try {
      $conn = db();
      $sql = "DELETE FROM schedules WHERE id = :id";
      $stmt = $conn->prepare(sql);
      $stmt->bindParam(':id', $scheduleId);
      $stmt->execute();
      return true;
    }
    catch (PDOException $e) {
      error_log("Database error: ".$e->getMessage());
      return false;
    }
  }
}




