<?php

namespace App\Backend\Services;

use App\Backend\Models\TopPoolResult;
use App\Backend\Repositories\TopPoolsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class TopPoolsService
{
    private const ALLOWED_TYPES = ['pisi', 'piex', 'pata', 'jeud'];
    private const MAX_RADIUS_KM = 50.0;

    /**
     * @return TopPoolResult[]
     */
    public static function findTopPools(string $postalCode, float $radiusKm, DateTimeImmutable $dateTime, ?string $type = null): array
    {
        $postalCode = trim($postalCode);
        if ($postalCode === '') {
            throw new InvalidArgumentException('postalCode is required.');
        }

        // Basic Canadian postal code validation (minimal)
        if (!preg_match('/^[A-Za-z]\d[A-Za-z][\s-]?\d[A-Za-z]\d$/', $postalCode)) {
            throw new InvalidArgumentException('postalCode must be a valid Canadian postal code.');
        }

        if (!is_numeric($radiusKm) || $radiusKm <= 0) {
            throw new InvalidArgumentException('radius must be a positive number.');
        }

        if ($radiusKm > self::MAX_RADIUS_KM) {
            $radiusKm = self::MAX_RADIUS_KM;
        }

        $now = new DateTimeImmutable();
        $sixMonths = $now->modify('+6 months');
        // Allow a 1-second leeway for nearly-simultaneous timestamps from caller
        if ($dateTime < $now->modify('-1 second')) {
            throw new InvalidArgumentException('dateTime cannot be in the past.');
        }
        if ($dateTime > $sixMonths) {
            throw new InvalidArgumentException('dateTime cannot be more than six months in the future.');
        }

        if ($type !== null) {
            $type = strtolower(trim($type));
            if (!in_array($type, self::ALLOWED_TYPES, true)) {
                throw new InvalidArgumentException('Invalid type value.');
            }
        }

        [$lat, $lng] = self::geocodePostalCode($postalCode);

        [$minLat, $maxLat, $minLng, $maxLng] = self::boundingBox($lat, $lng, $radiusKm);

        $timeStr = $dateTime->format('H:i:s');
        $candidates = TopPoolsRepository::findCandidates($minLat, $maxLat, $minLng, $maxLng, $dateTime->format('Y-m-d'), $timeStr, $type);

        // Group candidates by pool and pick the single best schedule per pool by time closeness
        $byPool = [];
        foreach ($candidates as $row) {
            $poolId = isset($row['id']) ? (int) $row['id'] : 0;
            if (!isset($byPool[$poolId])) {
                $byPool[$poolId] = [];
            }
            $byPool[$poolId][] = $row;
        }

        $results = [];
        foreach ($byPool as $poolId => $rows) {
            $best = null;
            $bestGap = PHP_INT_MAX;
            foreach ($rows as $row) {
                if (!isset($row['start_time'])) {
                    continue;
                }

                $gap = abs(strtotime($row['start_time']) - strtotime($timeStr));
                if ($gap < $bestGap) {
                    $bestGap = $gap;
                    $best = $row;
                }
            }

            if ($best === null) {
                continue;
            }

            if (!isset($best['latt']) || !isset($best['longt'])) {
                continue;
            }

            $poolLat = (float) $best['latt'];
            $poolLng = (float) $best['longt'];
            $distanceKm = self::approxDistanceKm($lat, $lng, $poolLat, $poolLng);
            if ($distanceKm > $radiusKm) {
                continue;
            }

            $tp = TopPoolResult::fromRowWithGap($best, $distanceKm, (int) $bestGap);
            $results[] = $tp;
        }

        // Optionally sort by time gap (closest schedules first)
        usort($results, fn($a, $b) => $a->getTimeGap() <=> $b->getTimeGap());

        return $results;
    }

    private static function geocodePostalCode(string $postalCode): array
    {
        $url = 'https://geocoder.ca/?locate=' . urlencode($postalCode) . '&geoit=XML&json=1';
        $geocoder = curl_init($url);
        curl_setopt($geocoder, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($geocoder, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);
        curl_setopt($geocoder, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($geocoder);
        $err = curl_error($geocoder);
        curl_close($geocoder);

        if ($response === false || $response === '') {
            throw new RuntimeException('Failed to geocode postal code: ' . $postalCode . '. cURL error: ' . $err);
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['latt'], $data['longt'])) {
            throw new RuntimeException('Failed to geocode postal code: ' . $postalCode);
        }

        $lat = is_numeric($data['latt']) ? (float) $data['latt'] : null;
        $lng = is_numeric($data['longt']) ? (float) $data['longt'] : null;

        if ($lat === null || $lng === null) {
            throw new RuntimeException('Invalid geocode response for: ' . $postalCode);
        }

        return [$lat, $lng];
    }

    private static function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $latRadius = $radiusKm / 110.574; // degrees latitude per km
        $lngRadius = $radiusKm / (111.320 * cos(deg2rad($lat))); // degrees longitude per km

        return [$lat - $latRadius, $lat + $latRadius, $lng - $lngRadius, $lng + $lngRadius];
    }

    private static function approxDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dy = ($lat2 - $lat1) * 110.574;
        $dx = ($lng2 - $lng1) * 111.320 * cos(deg2rad($lat1));
        return sqrt($dx * $dx + $dy * $dy);
    }
}

\class_alias(__NAMESPACE__ . '\\TopPoolsService', 'TopPoolsService');
