<?php

namespace App\Backend\Repositories;

use App\Backend\Models\TimeBlock;
use DateTime;
use PDO;
use PDOException;

class TimeBlockRepository { 
  public static  function getAllTimeBlocks(): array
  {
    $array = [];
    $counter = 0;
    try {
      $conn = \db();
      $sql = "SELECT * FROM time_blocks";
      $result = $conn->query($sql);
      $data = $result->fetchAll(PDO::FETCH_ASSOC);

      foreach ($data as &$row) {
          $timeBlock = new TimeBlock(
              $row['id'],
              $row['schedule_id'],
              $row['day_of_week'],
              $row['start_time'],
              $row['end_time'],
              $row['label']
          );
          $array[$counter++] = $timeBlock;
      }
      return $array;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return [];
    }
  }

  public static function getTimeBlockById(int $id): ?TimeBlock
  {
    try {
      $conn = \db();
      $sql = "SELECT * FROM time_blocks WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $timeBlockArr = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($timeBlockArr !== false) {
        return new TimeBlock(
          $timeBlockArr['id'],
          $timeBlockArr['schedule_id'],
          $timeBlockArr['day_of_week'],
          $timeBlockArr['start_time'],
          $timeBlockArr['end_time'],
          $timeBlockArr['label']
        );
      }
      return null;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null; 
    }
  }

  /**
   * @param int[] $scheduleIds
   * @return array<int, TimeBlock[]>
   */
  public static function getTimeBlocksByScheduleIds(array $scheduleIds): array
  {
    $scheduleIds = array_values(array_unique(array_filter(array_map('intval', $scheduleIds), static fn(int $id): bool => $id > 0)));
    if ($scheduleIds === []) {
      return [];
    }

    $placeholders = implode(', ', array_fill(0, count($scheduleIds), '?'));

    try {
      $conn = \db();
      $stmt = $conn->prepare(
        "SELECT * FROM time_blocks WHERE schedule_id IN ($placeholders) ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time, end_time"
      );
      $stmt->execute($scheduleIds);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $blocksByScheduleId = [];
      foreach ($rows as $row) {
        $scheduleId = (int) $row['schedule_id'];
        $blocksByScheduleId[$scheduleId][] = new TimeBlock(
          (int) $row['id'],
          $scheduleId,
          (string) $row['day_of_week'],
          (string) $row['start_time'],
          (string) $row['end_time'],
          $row['label'] !== null ? (string) $row['label'] : null
        );
      }

      return $blocksByScheduleId;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return [];
    }
  }

  public static function createTimeBlock(TimeBlock $newTimeBlock): int
  {
      $conn = \db();
    try {
      $conn->beginTransaction();
      $sql = "INSERT INTO time_blocks (day_of_week, start_time, end_time, label) VALUES (:day_of_week, :start_time, :end_time, :label)";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':day_of_week', $newTimeBlock->getDay());
      $stmt->bindParam(':start_time', $newTimeBlock->getStart());
      $stmt->bindParam(':end_time', $newTimeBlock->getEnd());
      $stmt->bindParam(':label', $newTimeBlock->getLabel());
      $stmt->execute();
      $conn->commit();
      return  (int) $conn->lastInsertId();

      } catch (PDOException $e) {
       error_log("Database error: " . $e->getMessage());
       return -1; 
     }
  }

  public static function updateTimeBlock(int $oldId, TimeBlock $timeBlock): bool
  {
    $conn = \db();

    if (!self::timeBlockExists($conn, $oldId)) {
        error_log("Time block with ID " .$oldId. " does not exist.");
        return false; 
    }

    try {
      $sql = "UPDATE time_blocks SET schedule_id = :schedule_id, day_of_week = :day_of_week, start_time = :start_time, end_time = :end_time, label = :label WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $oldId);
      $stmt->bindParam(':schedule_id', $timeBlock->getScheduleId());
      $stmt->bindParam(':day_of_week', $timeBlock->getDay());
      $stmt->bindParam(':start_time', $timeBlock->getStart());
      $stmt->bindParam(':end_time', $timeBlock->getEnd());
      $stmt->bindParam(':label', $timeBlock->getLabel());
      $stmt->execute();

      return true;

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return false; 
    }
  }

   private static function timeBlockExists(PDO $conn, int $id): bool
    {
        $stmt = $conn->prepare("SELECT 1 FROM time_blocks WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

  public static function deleteTimeBlock(int $id): bool
  {
     $conn = \db();
    try {
      $sql = "DELETE FROM time_blocks WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id);
      return $stmt->execute();

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return false; 
    }
  }

  
  public static function findTimeInSchedule(DateTime $time):? array {
    $conn = \db();
    try {
      $sql = "SELECT s.id as schedule_id, tb.* FROM schedules s JOIN time_blocks tb ON s.id = tb.schedule_id WHERE :time BETWEEN s.effective_date AND s.end_date";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':time', $time->format('Y-m-d H:i:s'));
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (empty($data)) {
        return null;
      }

      $timeBlocks = [];
      foreach ($data as &$row) {
          $timeBlock = new TimeBlock(
              $row['id'],
              $row['schedule_id'],
              $row['day_of_week'],
              $row['start_time'],
              $row['end_time'],
              $row['label']
          );
          $timeBlocks[] = $timeBlock;
      }
      return $timeBlocks;

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null; 
    }
  }

}

\class_alias(__NAMESPACE__ . '\\TimeBlockRepository', 'TimeBlockRepository');
