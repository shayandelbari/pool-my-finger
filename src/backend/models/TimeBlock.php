<?php

class TimeBlock
{
    private int $id;
    private Schedule $schedule;
    private string $day;
    private string $start;
    private string $end;
    private ?string $label;

    public function __construct(int $id, Schedule $schedule, string $day, string $start, string $end, ?string $label)
    {
        $this->id = $id;
        $this->schedule = $schedule;
        $this->day = $day;
        $this->start = $start;
        $this->end = $end;
        $this->label = $label;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule;
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