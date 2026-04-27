<?php

namespace App\Backend\Controllers;

use App\Backend\Models\TopPoolResult;
use App\Backend\Services\TopPoolsService;
use DateTimeImmutable;
use InvalidArgumentException;

class TopPoolsController
{
    public static function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            self::jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        try {
            $postalCode = $_GET['postalCode'] ?? null;
            $radius = $_GET['radius'] ?? null;
            $dateTimeRaw = $_GET['dateTime'] ?? null;
            $type = $_GET['type'] ?? null;

            if (!is_string($postalCode) || trim($postalCode) === '') {
                throw new InvalidArgumentException('postalCode is required.');
            }

            if ($radius === null || !is_numeric($radius)) {
                throw new InvalidArgumentException('radius is required and must be numeric.');
            }

            $radiusFloat = (float) $radius;

            $dateTime = null;
            if ($dateTimeRaw !== null && $dateTimeRaw !== '') {
                $dateTime = new DateTimeImmutable($dateTimeRaw);
            } else {
                $dateTime = new DateTimeImmutable();
            }

            $typeNorm = null;
            if ($type !== null && is_string($type) && trim($type) !== '') {
                $typeNorm = strtolower(trim($type));
            }

            $results = TopPoolsService::findTopPools((string) $postalCode, $radiusFloat, $dateTime, $typeNorm);

            $payload = ['pools' => array_map(fn(TopPoolResult $r): array => $r->toArray(), $results)];
            self::jsonResponse($payload);
        } catch (InvalidArgumentException $e) {
            self::jsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log('TopPoolsController error: ' . $e->getMessage());
            self::jsonResponse(['error' => 'Internal server error.'], 500);
        }
    }

    /** @param array<string,mixed> $payload */
    private static function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}

\class_alias(__NAMESPACE__ . '\\TopPoolsController', 'TopPoolsController');
