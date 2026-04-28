<?php

namespace App\Backend\Services;

use App\Backend\Models\Pool;
use App\Backend\Models\Schedule;
use App\Backend\Models\TimeBlock;
use DateTimeImmutable;
use DateTimeInterface;

class PoolSearchRelevanceService
{
    /**
     * @param Pool[] $pools
     * @param array<int, Schedule[]> $schedulesByPoolId
     * @param array<int, float> $distancesByPoolId
     * @return array<int, array{pool: Pool, distance: ?float, relevance: ?array<string, mixed>}>
     */
    public static function buildSearchResults(
        array $pools,
        array $schedulesByPoolId,
        ?DateTimeImmutable $requestedDateTime,
        array $distancesByPoolId = []
    ): array {
        $results = [];

        foreach ($pools as $pool) {
            if (!$pool instanceof Pool) {
                continue;
            }

            $poolId = $pool->getId();
            $relevance = null;

            if ($requestedDateTime !== null) {
                $relevance = self::evaluatePoolSchedules(
                    $schedulesByPoolId[$poolId] ?? [],
                    $requestedDateTime
                );

                if ($relevance === null) {
                    continue;
                }
            }

            $results[] = [
                'pool' => $pool,
                'distance' => array_key_exists($poolId, $distancesByPoolId)
                    ? (float) $distancesByPoolId[$poolId]
                    : null,
                'relevance' => $relevance,
            ];
        }

        usort($results, static function (array $left, array $right): int {
            $leftRelevance = $left['relevance'] ?? null;
            $rightRelevance = $right['relevance'] ?? null;

            $leftRank = self::sortRank($leftRelevance);
            $rightRank = self::sortRank($rightRelevance);
            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            $leftGap = self::sortGap($leftRelevance);
            $rightGap = self::sortGap($rightRelevance);
            if ($leftGap !== $rightGap) {
                return $leftGap <=> $rightGap;
            }

            $leftDistance = self::sortDistance($left['distance'] ?? null);
            $rightDistance = self::sortDistance($right['distance'] ?? null);
            if ($leftDistance !== $rightDistance) {
                return $leftDistance <=> $rightDistance;
            }

            return strcasecmp($left['pool']->getName(), $right['pool']->getName());
        });

        return $results;
    }

    /**
     * @param Schedule[] $schedules
     * @return array<string, mixed>|null
     */
    private static function evaluatePoolSchedules(array $schedules, DateTimeImmutable $requestedDateTime): ?array
    {
        $openCandidate = null;
        $upcomingCandidate = null;

        foreach ($schedules as $schedule) {
            if (!$schedule instanceof Schedule) {
                continue;
            }

            if (!self::isDateWithinSchedule($requestedDateTime, $schedule)) {
                continue;
            }

            foreach ($schedule->getTimeBlocks() as $timeBlock) {
                if (!$timeBlock instanceof TimeBlock) {
                    continue;
                }

                if (strcasecmp($timeBlock->getDay(), $requestedDateTime->format('l')) !== 0) {
                    continue;
                }

                $candidate = self::buildTimeCandidate($schedule, $timeBlock, $requestedDateTime);
                if ($candidate === null) {
                    continue;
                }

                if ($candidate['status'] === 'open') {
                    if (
                        $openCandidate === null
                        || $candidate['remainingSeconds'] < $openCandidate['remainingSeconds']
                    ) {
                        $openCandidate = $candidate;
                    }
                    continue;
                }

                if (
                    $upcomingCandidate === null
                    || $candidate['gapSeconds'] < $upcomingCandidate['gapSeconds']
                ) {
                    $upcomingCandidate = $candidate;
                }
            }
        }

        return $openCandidate ?? $upcomingCandidate;
    }

    private static function isDateWithinSchedule(DateTimeImmutable $requestedDateTime, Schedule $schedule): bool
    {
        $requestedDate = $requestedDateTime->format('Y-m-d');
        $effectiveDate = $schedule->getEffectiveDate()->format('Y-m-d');
        $endDate = $schedule->getEndDate()->format('Y-m-d');

        return $requestedDate >= $effectiveDate && $requestedDate <= $endDate;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildTimeCandidate(
        Schedule $schedule,
        TimeBlock $timeBlock,
        DateTimeImmutable $requestedDateTime
    ): ?array {
        $start = self::dateTimeForBlock($requestedDateTime, $timeBlock->getStart());
        $end = self::dateTimeForBlock($requestedDateTime, $timeBlock->getEnd());

        if ($end <= $requestedDateTime) {
            return null;
        }

        if ($start <= $requestedDateTime && $end > $requestedDateTime) {
            $remainingSeconds = $end->getTimestamp() - $requestedDateTime->getTimestamp();

            return [
                'status' => 'open',
                'isOpenAtRequestedTime' => true,
                'gapSeconds' => 0,
                'remainingSeconds' => $remainingSeconds,
                'timeChipText' => 'Open for ' . self::formatDuration($remainingSeconds) . ' more',
                'relevantSchedule' => self::formatRelevantSchedule($schedule, $timeBlock, $start, $end),
            ];
        }

        if ($start > $requestedDateTime) {
            return [
                'status' => 'upcoming',
                'isOpenAtRequestedTime' => false,
                'gapSeconds' => $start->getTimestamp() - $requestedDateTime->getTimestamp(),
                'remainingSeconds' => 0,
                'timeChipText' => sprintf(
                    'Open from %s to %s',
                    self::formatDisplayTime($start),
                    self::formatDisplayTime($end)
                ),
                'relevantSchedule' => self::formatRelevantSchedule($schedule, $timeBlock, $start, $end),
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function formatRelevantSchedule(
        Schedule $schedule,
        TimeBlock $timeBlock,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        return [
            'scheduleId' => $schedule->getId(),
            'scheduleType' => [
                'id' => $schedule->getType()->getId(),
                'name' => $schedule->getType()->getName(),
                'description' => $schedule->getType()->getDescription(),
            ],
            'effectiveDate' => $schedule->getEffectiveDate()->format('Y-m-d'),
            'endDate' => $schedule->getEndDate()->format('Y-m-d'),
            'timeBlock' => [
                'id' => $timeBlock->getId(),
                'day' => $timeBlock->getDay(),
                'start' => $timeBlock->getStart(),
                'end' => $timeBlock->getEnd(),
                'label' => $timeBlock->getLabel(),
                'displayStart' => self::formatDisplayTime($start),
                'displayEnd' => self::formatDisplayTime($end),
            ],
        ];
    }

    private static function dateTimeForBlock(DateTimeImmutable $requestedDateTime, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($requestedDateTime->format('Y-m-d') . ' ' . $time);
    }

    private static function formatDuration(int $seconds): string
    {
        $minutesTotal = max(1, (int) ceil($seconds / 60));
        $hours = intdiv($minutesTotal, 60);
        $minutes = $minutesTotal % 60;

        if ($hours > 0 && $minutes === 0) {
            return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($hours > 0) {
            return sprintf(
                '%d %s %d %s',
                $hours,
                $hours === 1 ? 'hour' : 'hours',
                $minutes,
                $minutes === 1 ? 'minute' : 'minutes'
            );
        }

        return $minutesTotal . ' ' . ($minutesTotal === 1 ? 'minute' : 'minutes');
    }

    private static function formatDisplayTime(DateTimeInterface $dateTime): string
    {
        return strtolower($dateTime->format('g:i A'));
    }

    /**
     * @param array<string, mixed>|null $relevance
     */
    private static function sortRank(?array $relevance): int
    {
        if ($relevance === null) {
            return 0;
        }

        return ($relevance['status'] ?? '') === 'open' ? 0 : 1;
    }

    /**
     * @param array<string, mixed>|null $relevance
     */
    private static function sortGap(?array $relevance): int
    {
        if ($relevance === null) {
            return 0;
        }

        return isset($relevance['gapSeconds']) ? (int) $relevance['gapSeconds'] : PHP_INT_MAX;
    }

    private static function sortDistance(?float $distance): float
    {
        return $distance !== null ? $distance : INF;
    }
}

\class_alias(__NAMESPACE__ . '\\PoolSearchRelevanceService', 'PoolSearchRelevanceService');
