<?php

namespace App\Backend\Repositories;

use App\Backend\Models\TimeBlock;
use DateTime;
use PDO;
use PDOException;

require_once '../models/TimeBlock.php';

class TimeBlockRepository { 
  public function getAllTimeBlocks(): array
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

  public function getTimeBlockById(int $id): ?getTimeBlock 
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

  public function createTimeBlock(TimeBlock $newTimeBlock): ?TimeBlock
  {
    try {
      $conn = \db();
      $sql = "INSERT INTO time_blocks (schedule_id, day_of_week, start_time, end_time, label) VALUES (:schedule_id, :day_of_week, :start_time, :end_time, :label)";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':schedule_id', $newTimeBlock->getSchedule());
      $stmt->bindParam(':day_of_week', $newTimeBlock->getDay());
      $stmt->bindParam(':start_time', $newTimeBlock->getStart());
      $stmt->bindParam(':end_time', $newTimeBlock->getEnd());
      $stmt->bindParam(':label', $newTimeBlock->getLabel());
      $stmt->execute();

      $timeBlockId = $conn->lastInsertedId();

      return $this->getTimeBlockById($timeBlockId);
      } catch (PDOException $e) {
       error_log("Database error: " . $e->getMessage());
       return null; 
     }
  }

  public function updateTimeBlock(int $oldId, TimeBlock $timeBlock): ?TimeBlock
  {
    try {
      $conn = \db();
      $sql = "UPDATE time_blocks SET schedule_id = :schedule_id, day_of_week = :day_of_week, start_time = :start_time, end_time = :end_time, label = :label WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $oldId);
      $stmt->bindParam(':schedule_id', $timeBlock->getSchedule());
      $stmt->bindParam(':day_of_week', $timeBlock->getDay());
      $stmt->bindParam(':start_time', $timeBlock->getStart());
      $stmt->bindParam(':end_time', $timeBlock->getEnd());
      $stmt->bindParam(':label', $timeBlock->getLabel());
      $stmt->execute();
      
      return $this->getTimeBlockById($timeBlock->getId());
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null; 
    }
  }

  public function deleteTimeBlock(int $id): bool
  {
    try {
      $conn = \db();
      $sql = "DELETE FROM time_blocks WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id);
      return $stmt->execute();

    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return false; 
    }
  }
}