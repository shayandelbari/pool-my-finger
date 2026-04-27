<?php

namespace App\Backend\Controllers;
use App\Backend\Models\Schedule;
use App\Backend\Models\ScheduleType;
use App\Backend\Repositories\ScheduleRepository;
use App\Backend\Repositories\ScheduleTypeRepository;
use App\Backend\Services\ScheduleTimeBlockService;

 
class ScheduleController {
  public static function indexTypes(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      self::jsonResponse(['error' => 'Method not allowed.'], 405);
      return;
    }

    $types = ScheduleTypeRepository::getAllScheduleTypes();
    self::jsonResponse([
      'types' => array_map(
        fn(ScheduleType $type): array => [
          'id' => $type->getId(),
          'name' => $type->getName(),
          'description' => $type->getDescription(),
        ],
        $types
      ),
    ]);
  }

  //Controller layer: this endpoint only handles HTTP concerns (METHODS) and delegates schedule listing to the service.

  //List all schedules. This is a public endpoint that anyone can hit to get the schedule data.
  public function index(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      self::jsonResponse(['error' => 'Method not allowed.'], 405);
      return;
    }

    try {
      $schedules = ScheduleTimeBlockService::getAllSchedules();
      self::jsonResponse([
        'schedules' => array_map(fn(Schedule $schedule): array => self::formatSchedule($schedule), $schedules),
      ]);
    } catch (\Exception $e) {
      self::jsonResponse(['error' => $e->getMessage()], 400);
    }
  }

  public static function showByPoolId(int $poolId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      self::jsonResponse(['error' => 'Method not allowed.'], 405);
      return;
    }

    try {
      $schedules = ScheduleTimeBlockService::getScheduleByPoolId($poolId);
      self::jsonResponse([
        'schedules' => array_map(fn(Schedule $schedule): array => self::formatSchedule($schedule), $schedules),
      ]);
      
    } catch (\Exception $e) {
      self::jsonResponse(['error' => $e->getMessage()], 400);
    }
  }



  //this is like json encode
  private static function formatSchedule(Schedule $schedule): array {
    return [
      'id' => $schedule->getId(),
      'poolId' => $schedule->getPool()->getId(),
      'type' => [
        'id' => $schedule->getType()->getId(),
        'name' => $schedule->getType()->getName(),
        'description' => $schedule->getType()->getDescription(),
      ],
      'effectiveDate' => $schedule->getEffectiveDate()->format('Y-m-d'),
      'endDate' => $schedule->getEndDate()->format('Y-m-d'),
      'timeBlocks' => array_map(
        static fn($timeBlock): array => [
          'id' => $timeBlock->getId(),
          'day' => $timeBlock->getDay(),
          'start' => $timeBlock->getStart(),
          'end' => $timeBlock->getEnd(),
          'label' => $timeBlock->getLabel(),
        ],
        $schedule->getTimeBlocks()
      ),
    ];
  }

  //Controller helper: emitting HTTP status and JSON payload is endpoint-layer behavior.
  private static function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
  }
}

\class_alias(__NAMESPACE__.'\\ScheduleController', 'ScheduleController');
