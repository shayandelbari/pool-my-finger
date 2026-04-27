<?php

namespace App\Backend\Models;

use DateTime;

class Schedule
{
    private int $id;
    private Pool $pool;
    private ScheduleType $type;
    private DateTime $effectiveDate;
    private DateTime $endDate;
    private DateTime $createdAt;
    /**
     * @var TimeBlock[]
     */
    private array $timeBlocks;

    public function __construct(int $id, Pool $pool, ScheduleType $type, DateTime $effectiveDate, DateTime $endDate, DateTime $createdAt, array $timeBlocks = [])
    {
        $this->id = $id;
        $this->pool = $pool;
        $this->type = $type;
        $this->effectiveDate = $effectiveDate;
        $this->endDate = $endDate;
        $this->createdAt = $createdAt;
        $this->timeBlocks = $timeBlocks;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPool(): Pool
    {
        return $this->pool;
    }

    public function getType(): ScheduleType
    {
        return $this->type;
    }

    public function getEffectiveDate(): DateTime
    {
        return $this->effectiveDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return TimeBlock[]
     */
    public function getTimeBlocks(): array
    {
        return $this->timeBlocks;
    }
}

\class_alias(__NAMESPACE__ . '\\Schedule', 'Schedule');
