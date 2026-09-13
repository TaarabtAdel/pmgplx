<?php

namespace App\Support\DaoTao;

class DatGpsCoordinateExtractor
{
    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array{lat: float, lng: float}>
     */
    public static function collectPoints(array $segments): array
    {
        $points = [];

        foreach ($segments as $segment) {
            $coordinates = $segment['ListCoordinate'] ?? null;
            if (! is_array($coordinates)) {
                continue;
            }

            foreach ($coordinates as $coordinate) {
                if (! is_array($coordinate)) {
                    continue;
                }

                $lat = self::toFloat($coordinate['Latitude'] ?? $coordinate['latitude'] ?? $coordinate['ViDo'] ?? null);
                $lng = self::toFloat($coordinate['Longitude'] ?? $coordinate['longitude'] ?? $coordinate['KinhDo'] ?? null);

                if ($lat === null || $lng === null) {
                    continue;
                }

                $points[] = [
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            }
        }

        return $points;
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $points
     * @return array{start: array{lat: float, lng: float}, end: array{lat: float, lng: float}}|null
     */
    public static function endpoints(array $points): ?array
    {
        if ($points === []) {
            return null;
        }

        $start = $points[0];
        $end = $points[count($points) - 1];

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private static function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
