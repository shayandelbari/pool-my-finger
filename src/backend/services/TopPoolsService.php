<?php

namespace App\Backend\Services;

use App\Backend\Models\TopPoolResult;
use App\Backend\Repositories\TopPoolsRepository;
use DateTimeImmutable;
use InvalidArgumentException;

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
        if ($dateTime < $now) {
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

        $candidates = TopPoolsRepository::findCandidates($minLat, $maxLat, $minLng, $maxLng, $dateTime->format('Y-m-d'), $dateTime->format('H:i:s'), $type);

        $results = [];
        foreach ($candidates as $row) {
            if (!isset($row['latt']) || !isset($row['longt'])) {
                continue;
            }

            $poolLat = (float) $row['latt'];
            $poolLng = (float) $row['longt'];

            $distanceKm = self::approxDistanceKm($lat, $lng, $poolLat, $poolLng);
            if ($distanceKm > $radiusKm) {
                continue;
            }

            $tp = TopPoolResult::fromRow($row, $distanceKm);
            $results[] = $tp;
        }

        return $results;
    }

    private static function geocodePostalCode(string $postalCode): array
    {
        // Minimal stub geocoder — replace with real geocoding in production.
        // Return Ottawa center as a default.
        return [45.4215, -75.6972];
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
