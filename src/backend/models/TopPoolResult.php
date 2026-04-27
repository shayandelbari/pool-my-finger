<?php

namespace App\Backend\Models;

class TopPoolResult
{
    private array $pool;
    private array $schedule;
    private float $distance;
    private int $timeGapSeconds = 0;

    public function __construct(array $pool, array $schedule, float $distance)
    {
        $this->pool = $pool;
        $this->schedule = $schedule;
        $this->distance = $distance;
    }

    public static function fromRow(array $row, float $distance): self
    {
        $pool = [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'name' => $row['name'] ?? null,
            'address' => $row['full_address'] ?? null,
            'imageUrl' => $row['primary_image_url'] ?? null,
            'website' => $row['website'] ?? null,
            'map' => $row['map_link'] ?? null,
            'latitude' => isset($row['latt']) ? (float) $row['latt'] : null,
            'longitude' => isset($row['longt']) ? (float) $row['longt'] : null,
            'phone' => $row['phone'] ?? null,
            'active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
            'createdAt' => isset($row['created_at']) ? $row['created_at'] : null,
        ];

        $schedule = [
            'id' => isset($row['schedule_id']) ? (int) $row['schedule_id'] : null,
            'effectiveDate' => $row['effective_date'] ?? null,
            'endDate' => $row['end_date'] ?? null,
            'dayOfWeek' => $row['day_of_week'] ?? null,
            'startTime' => $row['start_time'] ?? null,
            'endTime' => $row['end_time'] ?? null,
            'label' => $row['label'] ?? null,
        ];

        return new self($pool, $schedule, $distance);
    }

    public static function fromRowWithGap(array $row, float $distance, int $timeGapSeconds): self
    {
        $instance = self::fromRow($row, $distance);
        $instance->timeGapSeconds = $timeGapSeconds;
        return $instance;
    }

    public function toArray(): array
    {
        $sched = $this->schedule;
        $sched['timeGapSeconds'] = $this->timeGapSeconds;

        return [
            'pool' => $this->pool,
            'relevantSchedule' => $sched,
            'distance' => round($this->distance, 2),
        ];
    }

    public function getTimeGap(): int
    {
        return $this->timeGapSeconds;
    }
}

\class_alias(__NAMESPACE__ . '\\TopPoolResult', 'TopPoolResult');
