<?php

namespace App\Backend\Repositories;

use App\Backend\Models\Schedule;
use App\Backend\Models\Pool;
use App\Backend\Models\ScheduleType;
use DateTime;
use PDO;
use PDOException;

class ScheduleRepository {
  public static function getAllSchedules(): array
    {
        try {
            $conn = \db();
            $sql = self::baseReadSql() . " ORDER BY s.effective_date DESC, s.id DESC";
            $result = $conn->query($sql);
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            return self::hydrateSchedules($rows);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
  public static function getScheduleById(int $id): ?Schedule 
  {
    try {
      $conn = \db();
      $sql = self::baseReadSql() . " WHERE s.id = :id LIMIT 1";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $scheduleArr = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($scheduleArr === false) {
        return null;
      }

      $schedules = self::hydrateSchedules([$scheduleArr]);
      return $schedules[0] ?? null;
    } catch (PDOException $e) {
      error_log("Database error: " . $e->getMessage());
      return null;
    }
  }

  public static function getScheduleByPoolId(int $poolId): array {
    try {
      $conn = \db();
      $sql = self::baseReadSql() . " WHERE s.pool_id = :pool_id ORDER BY s.effective_date DESC, s.id DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':pool_id', $poolId, PDO::PARAM_INT);
      $stmt->execute();
      $schedulesArr = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return self::hydrateSchedules($schedulesArr);
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

  private static function baseReadSql(): string
  {
    return "SELECT
        s.id,
        s.pool_id,
        s.schedule_type_id,
        s.effective_date,
        s.end_date,
        s.created_at,
        p.name AS pool_name,
        p.full_address,
        p.primary_image_url,
        p.website,
        p.map_link,
        p.latt,
        p.longt,
        p.phone,
        p.is_active,
        p.created_at AS pool_created_at,
        st.name AS schedule_type_name,
        st.description AS schedule_type_description
      FROM schedules s
      INNER JOIN pools p ON p.id = s.pool_id
      INNER JOIN schedule_types st ON st.id = s.schedule_type_id";
  }

  /**
   * @param array<int, array<string, mixed>> $rows
   * @return Schedule[]
   */
  private static function hydrateSchedules(array $rows): array
  {
    if ($rows === []) {
      return [];
    }

    $scheduleIds = array_map(static fn(array $row): int => (int) $row['id'], $rows);
    $timeBlocksByScheduleId = TimeBlockRepository::getTimeBlocksByScheduleIds($scheduleIds);

    $schedules = [];
    foreach ($rows as $row) {
      $pool = new Pool(
        (int) $row['pool_id'],
        (string) $row['pool_name'],
        $row['full_address'] !== null ? (string) $row['full_address'] : null,
        $row['primary_image_url'] !== null ? (string) $row['primary_image_url'] : null,
        $row['website'] !== null ? (string) $row['website'] : null,
        $row['map_link'] !== null ? (string) $row['map_link'] : null,
        $row['latt'] !== null ? (float) $row['latt'] : null,
        $row['longt'] !== null ? (float) $row['longt'] : null,
        $row['phone'] !== null ? (string) $row['phone'] : null,
        (bool) $row['is_active'],
        new DateTime((string) $row['pool_created_at'])
      );

      $scheduleType = new ScheduleType(
        (int) $row['schedule_type_id'],
        (string) $row['schedule_type_name'],
        $row['schedule_type_description'] !== null ? (string) $row['schedule_type_description'] : null
      );

      $scheduleId = (int) $row['id'];
      $schedules[] = new Schedule(
        $scheduleId,
        $pool,
        $scheduleType,
        new DateTime((string) $row['effective_date']),
        new DateTime((string) $row['end_date']),
        new DateTime((string) $row['created_at']),
        $timeBlocksByScheduleId[$scheduleId] ?? []
      );
    }

    return $schedules;
  }

}


\class_alias(__NAMESPACE__ . '\\ScheduleRepository', 'ScheduleRepository');
