<?php

namespace App\Backend\Services;

use App\Backend\Models\Pool;
use App\Backend\Repositories\PoolRepository;
use App\Backend\Repositories\ScheduleRepository;
use App\Backend\Repositories\TopPoolsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class TopPoolsService
{
    private const ALLOWED_TYPES = ['pisi', 'piex', 'pata', 'jeud'];
    private const MAX_RADIUS_KM = 50.0;

    /**
     * @param string[] $types
     * @return array<int, array{pool: Pool, distance: ?float, relevance: ?array<string, mixed>}>
     */
    public static function findTopPools(
        string $postalCode,
        float $radiusKm,
        DateTimeImmutable $dateTime,
        array $types = []
    ): array {
        $postalCode = trim($postalCode);
        if ($postalCode === '') {
            throw new InvalidArgumentException('postalCode is required.');
        }

        if (!preg_match('/^[A-Za-z]\d[A-Za-z][\s-]?\d[A-Za-z]\d$/', $postalCode)) {
            throw new InvalidArgumentException('postalCode must be a valid Canadian postal code.');
        }

        if (!is_numeric($radiusKm) || $radiusKm <= 0) {
            throw new InvalidArgumentException('distance must be a positive number.');
        }

        if ($radiusKm > self::MAX_RADIUS_KM) {
            $radiusKm = self::MAX_RADIUS_KM;
        }

        $normalizedTypes = self::normalizeTypes($types);
        [$lat, $lng] = self::geocodePostalCode($postalCode);
        [$minLat, $maxLat, $minLng, $maxLng] = self::boundingBox($lat, $lng, $radiusKm);

        $candidateRows = TopPoolsRepository::findNearbyPools($minLat, $maxLat, $minLng, $maxLng, $normalizedTypes);
        if ($candidateRows === []) {
            return [];
        }

        $distancesByPoolId = [];
        foreach ($candidateRows as $row) {
            $poolId = isset($row['pool_id']) ? (int) $row['pool_id'] : (int) ($row['id'] ?? 0);
            if ($poolId <= 0 || !isset($row['latt'], $row['longt'])) {
                continue;
            }

            $distanceKm = self::approxDistanceKm($lat, $lng, (float) $row['latt'], (float) $row['longt']);
            if ($distanceKm > $radiusKm) {
                continue;
            }

            $distancesByPoolId[$poolId] = $distanceKm;
        }

        if ($distancesByPoolId === []) {
            return [];
        }

        $poolIds = array_keys($distancesByPoolId);
        $pools = PoolRepository::getPoolsByIds($poolIds);
        if ($pools === []) {
            return [];
        }

        $schedulesByPoolId = ScheduleRepository::getSchedulesByPoolIds($poolIds);

        return PoolSearchRelevanceService::buildSearchResults(
            $pools,
            $schedulesByPoolId,
            $dateTime,
            $distancesByPoolId
        );
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    private static function normalizeTypes(array $types): array
    {
        $normalized = [];
        foreach ($types as $type) {
            if (!is_string($type)) {
                throw new InvalidArgumentException('Each type filter must be a string value.');
            }

            $typeName = strtolower(trim($type));
            if ($typeName === '') {
                continue;
            }

            if (!in_array($typeName, self::ALLOWED_TYPES, true)) {
                throw new InvalidArgumentException('Invalid type value.');
            }

            $normalized[] = $typeName;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array{0: float, 1: float}
     */
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

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private static function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $latRadius = $radiusKm / 110.574;
        $lngRadius = $radiusKm / (111.320 * cos(deg2rad($lat)));

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
