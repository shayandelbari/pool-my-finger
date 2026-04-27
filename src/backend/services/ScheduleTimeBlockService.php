<?php
namespace App\Backend\Services;

use App\Backend\Models\Schedule;
use App\Backend\Models\TimeBlock;
use InvalidArgumentException;
use RuntimeException;
use DateTime;
use App\Backend\Repositories\ScheduleRepository;
use App\Backend\Repositories\TimeBlockRepository;



class ScheduleTimeBlockService {
  public static function getAllSchedules(): array {
    return ScheduleRepository::getAllSchedules();
  }

  public static function getAllTimeBlocks(): array {
    return TimeBlockRepository::getAllTimeBlocks();
  }

  public static function getScheduleById(int $id): ?Schedule {
    if ($id <= 0) {
      throw new InvalidArgumentException('Schedule id must be a positive integer.');
    }
    return ScheduleRepository::getScheduleById($id);
  }

  public static function getScheduleByPoolId(int $id): array {
    if ($id <= 0) {
      throw new InvalidArgumentException('Pool id must be a positive integer.');
    }
    return ScheduleRepository::getScheduleByPoolId($id);
  }

  public static function getTimeBlockById(int $id): ?TimeBlock {
    if ($id <=0) {
      throw new InvalidArgumentException('TimeBlock id must be a positive integer.');
    }
    return TimeBlockRepository::getTimeBlockById($id);
  }

  public static function createSchedule(array $payload): Schedule {

    //validate that the payload has all the necessary fields.
    foreach ($payload as $key => $value) {
      self::validator($value);
    }

    //Validate that the schedule is not already in the database. In the repo. 

    if (ScheduleRepository::getScheduleIdByFields(
        $payload['pool_id'],
        $payload['schedule_type_id'],
        $payload['effective_date'],
        $payload['end_date']
    ) === -1) {
        throw new RuntimeException('Schedule already exists.');
    }

    $name = $payload['pool_id'];
    $scheduleType = $payload['schedule_type_id'];
    $effectiveDate = $payload['effective_date'];
    $endDate = $payload['end_date'];

    $newSchedule = new Schedule(0, $name, $scheduleType, $effectiveDate, $endDate, new DateTime());

    $schedule_id = ScheduleRepository::createSchedule($newSchedule);

    if ($schedule_id <= 0) {
      throw new RuntimeException('Failed to create schedule.');
    }

    $createdSchedule = ScheduleRepository::getScheduleById($schedule_id);

    if ($createdSchedule === null) {
      throw new RuntimeException('Schedule was created but could not be retrieved,');
    }

    return $createdSchedule;
  }

  //payload will contain the schedule id through the fields
  public static function createTimeBlock(array $payload): TimeBlock {
    foreach ($payload as $key => $value) {
      self::validator($value);
    }

    //get the id of the schedule
    $scheduleId = ScheduleRepository::getScheduleIdByFields(
      $payload['pool_id'],
      $payload['schedule_type_id'],
      $payload['effective_date'],
      $payload['end_date']
    );

    if ($scheduleId === -1) {
      throw new RuntimeException('Associated schedule not found.');
    }

    // Create the time block
    $timeBlock = new TimeBlock(0, $scheduleId, $payload['day'], $payload['start_time'], $payload['end_time'], $payload['label']);

    $timeblockId = TimeBlockRepository::createTimeBlock($timeBlock);
    
    if ($timeblockId <= 0) {
      throw new RuntimeException('Failed to create time block.');
    }

    $createdTimeBlock = TimeBlockRepository::getTimeBlockById($timeblockId);
    if ($createdTimeBlock === null) {
      throw new RuntimeException('Time block was created but could not be retrieved.');
    }
  
    return $createdTimeBlock;
  }

  public static function updateSchedule(int $id, array $payload): ?Schedule {
    if ($id <= 0) {
      throw new InvalidArgumentException('Schedule id must be a positive integer.');
    }

    $existing = ScheduleRepository::getScheduleById($id);
    if ($existing === null) {
      return null;
    }

    foreach ($payload as $key => $value) {
      self::validator($value);
    }
    
    $name = $payload['pool_id'];
    $scheduleType = $payload['schedule_type_id'];
    $effectiveDate = $payload['effective_date'];
    $endDate = $payload['end_date'];

    $schedule = new Schedule($id, $name, $scheduleType, $effectiveDate, $endDate, $existing->getCreatedAt());

    if (!ScheduleRepository::updateSchedule($id, $schedule)) {
      throw new RuntimeException('Failed to update schedule.');
    }
    return ScheduleRepository::getScheduleById($id);
  }

  public static function updateTimeBlock(int $id, array $payload): ?TimeBlock {
    if ($id <= 0) {
      throw new InvalidArgumentException('TimeBlock id must be a positive integer.');
    }

    $existing = TimeBlockRepository::getTimeBlockById($id);
    if ($existing === null) {
      return null;
    }

    foreach ($payload as $key => $value) {
      self::validator($value);
    }

    //get the id of the schedule
    $scheduleId = ScheduleRepository::getScheduleIdByFields(
      $payload['pool_id'],
      $payload['schedule_type_id'],
      $payload['effective_date'],
      $payload['end_date']
    );

    if ($scheduleId === -1) {
      throw new RuntimeException('Associated schedule not found.');
    }

    $timeBlock = new TimeBlock($id, $scheduleId, $payload['day'], $payload['start_time'], $payload['end_time'], $payload['label']);

    if (!TimeBlockRepository::updateTimeBlock($id, $timeBlock)) {
      throw new RuntimeException('Failed to update time block.');
    }
    
    return TimeBlockRepository::getTimeBlockById($id);
  }

  //it gets the the schedule of a particular week.
  //find out if the time falls under a particular schedule.
  //Then get the id of the schedule row, and get all the time blocks that have that schedule id, and return the timeBlocks. 
  public static function getValidSchedules(DateTime $time): ?array {
    if ($time === null) {
      throw new InvalidArgumentException('DateTime parameter cannot be null.');
    }

    $timeBlocks = TimeBlockRepository::findTimeInSchedule($time);

    if (empty($timeBlocks)) {
      throw new RuntimeException('No matching schedules found.');
    }

    if ($timeBlocks === null) {
      throw new RuntimeException('Failed to find schedules for the given time.');
    }

    return $timeBlocks;
  }

  private static function validator(string $data): void 
  {
    if ($data === '') {
      throw new InvalidArgumentException("Pool type name cannot be empty.");
    }
  }
  
}