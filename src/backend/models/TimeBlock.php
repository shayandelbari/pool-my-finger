<?php

namespace App\Backend\Models;

class TimeBlock
{
    private int $id;
    private int $schedule_id;
    private string $day;
    private string $start;
    private string $end;
    private ?string $label;

    public function __construct(int $id, int $schedule_id, string $day, string $start, string $end, ?string $label)
    {
        $this->id = $id;
        $this->schedule_id = $schedule_id;
        $this->day = $day;
        $this->start = $start;
        $this->end = $end;
        $this->label = $label;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getScheduleId(): int
    {
        return $this->schedule_id;
    }

    public function getDay(): string
    {
        return $this->day;
    }

    public function getStart(): string
    {
        return $this->start;
    }

    public function getEnd(): string
    {
        return $this->end;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}

\class_alias(__NAMESPACE__ . '\\TimeBlock', 'TimeBlock');