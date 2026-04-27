<?php
namespace App\Backend\Services;

use App\Backend\Models\ScheduleType;
use InvalidArgumentException;
use RuntimeException;
use DateTime;
use App\Backend\Repositories\ScheduleTypeRepository;

class ScheduleTypeService {
  public static function getAllSchedulesTypes(): array {
    return ScheduleTypeRepository::getAllScheduleTypes();
  }

  public static function getScheduleTypeById(int $id): ?ScheduleType {
    if ($id <= 0) {
      throw new InvalidArgumentException('Schedule id must be a positive integer.');
    }
    return ScheduleTypeRepository::getScheduleTypesById($id);
  }

  public static function createScheduleType(array $payload): ScheduleType {

    //validate that the payload has all the necessary fields.
    foreach ($payload as $key => $value) {
      self::validator($value);
    }

    $name = $payload['name'];
    $description = $payload['description'];

    $newSchedule = new ScheduleType(0, $name, $description);

    $scheduleTypeId = ScheduleTypeRepository::createScheduleType($newSchedule);

    if ($scheduleTypeId <= 0) {
      throw new RuntimeException('Failed to create schedule type.');
    }

    $createdSchedule = ScheduleTypeRepository::getScheduleTypesById($scheduleTypeId);
    if ($createdSchedule === null) {
      throw new RuntimeException('Schedule type was created but could not be loaded');
    }

    return $createdSchedule;
  }

  public static function updateType(int $id, array $payload): ?ScheduleType
    {
       if ($id <= 0) {
      throw new InvalidArgumentException("Schedule type id must be a positive integer.");
    }
    $existingType = ScheduleTypeRepository::getScheduleTypesById($id);
    if ($existingType === null) {
      return null;
    }
    foreach ($payload as $key=>$value) {
      self::validator($value);
    }

    
    $name = $payload['name'];
    $description = $payload['description'];

    $updatedScheduleType = new ScheduleType($id, $name, $description);

    $isUpdated = ScheduleTypeRepository::updateScheduleType($id, $updatedScheduleType);

    if (!$isUpdated) {
      return null;
    }

    return ScheduleTypeRepository::getScheduleTypesById($id);

    }

    public static function deleteType(int $id): bool
    {
      if ($id <= 0) {
        throw new InvalidArgumentException("Schedule type id must be a positive integer.");
      }
      return ScheduleTypeRepository::deleteScheduleType($id);
    }

  private static function validator(string $data): void 
  {
    if ($data === '') {
      throw new InvalidArgumentException("Schedule type name cannot be empty.");
    }
  }
}