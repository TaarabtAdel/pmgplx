<?php

namespace App\Support\DaoTao;

/**
 * Tóm tắt tuyến GPS từ XeOnline + tên điểm qua Nominatim.
 */
class DatTuyenDuongGpsMatcher
{
    public function __construct(
        private readonly DatNominatimReverseGeocode $nominatim = new DatNominatimReverseGeocode(),
    ) {}

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    public function resolve(array $segments): ?string
    {
        $analysis = $this->analyze($segments);

        return $analysis['ten_tuyen_mac_dinh'] !== '' ? $analysis['ten_tuyen_mac_dinh'] : null;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return array<string, mixed>
     */
    public function analyze(array $segments): array
    {
        $segmentRouteName = $this->segmentRouteName($segments);
        $points = DatGpsCoordinateExtractor::collectPoints($segments);
        $endpoints = DatGpsCoordinateExtractor::endpoints($points);
        $gpsSummary = $endpoints !== null
            ? $this->summarizeGps($segments, $points, $endpoints)
            : null;

        $nominatimBlock = $this->resolveNominatim($endpoints);
        $tenTuyenNominatim = $nominatimBlock['ten_tuyen_goi'];

        return [
            'ten_tuyen_mac_dinh' => $segmentRouteName ?? $tenTuyenNominatim ?? $gpsSummary ?? '',
            'tong_km' => $this->totalKm($segments),
            'so_diem_gps' => count($points),
            'so_segment' => count($segments),
            'ten_tuyen_trong_api' => $segmentRouteName,
            'ten_tuyen_nominatim' => $tenTuyenNominatim,
            'ten_diem_dau' => $nominatimBlock['ten_diem_dau'],
            'ten_diem_cuoi' => $nominatimBlock['ten_diem_cuoi'],
            'list_coordinate' => $this->flattenListCoordinate($segments),
            'segments' => $segments,
            'diem_dau' => $this->endpointPayload($endpoints['start'] ?? null, $nominatimBlock['diem_dau']),
            'diem_cuoi' => $this->endpointPayload($endpoints['end'] ?? null, $nominatimBlock['diem_cuoi']),
            'nominatim' => [
                'diem_dau' => $nominatimBlock['diem_dau'],
                'diem_cuoi' => $nominatimBlock['diem_cuoi'],
                'ten_tuyen_goi' => $tenTuyenNominatim,
                'loi' => $nominatimBlock['loi'],
            ],
            'tom_tat_gps' => $gpsSummary,
        ];
    }

    /**
     * @param  array{start: array{lat: float, lng: float}, end: array{lat: float, lng: float}}|null  $endpoints
     * @return array{
     *     ten_diem_dau: string|null,
     *     ten_diem_cuoi: string|null,
     *     ten_tuyen_goi: string|null,
     *     diem_dau: array<string, mixed>|null,
     *     diem_cuoi: array<string, mixed>|null,
     *     loi: string|null
     * }
     */
    private function resolveNominatim(?array $endpoints): array
    {
        $result = [
            'ten_diem_dau' => null,
            'ten_diem_cuoi' => null,
            'ten_tuyen_goi' => null,
            'diem_dau' => null,
            'diem_cuoi' => null,
            'loi' => null,
        ];

        if ($endpoints === null) {
            return $result;
        }

        $errors = [];

        try {
            $startLookup = $this->nominatim->lookupForCoordinate(
                $endpoints['start']['lat'],
                $endpoints['start']['lng']
            );
            $result['diem_dau'] = $startLookup;
            $result['ten_diem_dau'] = $this->nominatim->displayLabelFromLookup($startLookup);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $endLookup = $this->nominatim->lookupForCoordinate(
                $endpoints['end']['lat'],
                $endpoints['end']['lng']
            );
            $result['diem_cuoi'] = $endLookup;
            $result['ten_diem_cuoi'] = $this->nominatim->displayLabelFromLookup($endLookup);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }

        $result['ten_tuyen_goi'] = $this->composeRouteName(
            $result['ten_diem_dau'],
            $result['ten_diem_cuoi'],
        );

        if ($errors !== []) {
            $result['loi'] = implode(' | ', $errors);
        }

        return $result;
    }

    private function composeRouteName(?string $startLabel, ?string $endLabel): ?string
    {
        if ($startLabel === null && $endLabel === null) {
            return null;
        }

        if ($startLabel === null) {
            return $endLabel;
        }

        if ($endLabel === null) {
            return $startLabel;
        }

        if (mb_strtolower($startLabel) === mb_strtolower($endLabel)) {
            return $startLabel;
        }

        return $startLabel.' - '.$endLabel;
    }

    /**
     * @param  array{lat: float, lng: float}|null  $endpoint
     * @param  array<string, mixed>|null  $nominatim
     * @return array{lat: float, lng: float, ten: string|null, str_point: string}|null
     */
    private function endpointPayload(?array $endpoint, ?array $nominatim): ?array
    {
        if ($endpoint === null) {
            return null;
        }

        $label = is_array($nominatim)
            ? $this->nominatim->displayLabelFromLookup($nominatim)
            : null;

        return [
            'lat' => $endpoint['lat'],
            'lng' => $endpoint['lng'],
            'ten' => $label,
            'str_point' => $this->formatCoordinate($endpoint),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    private function flattenListCoordinate(array $segments): array
    {
        $coordinates = [];

        foreach ($segments as $segment) {
            $list = $segment['ListCoordinate'] ?? null;
            if (! is_array($list)) {
                continue;
            }

            foreach ($list as $item) {
                if (is_array($item)) {
                    $coordinates[] = $item;
                }
            }
        }

        return $coordinates;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private function segmentRouteName(array $segments): ?string
    {
        foreach ($segments as $segment) {
            foreach (['TenTuyenDuong', 'tenTuyenDuong', 'TuyenDuong', 'tuyenDuong', 'TenCungDuong', 'CungDuong'] as $key) {
                $value = trim((string) ($segment[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @param  list<array{lat: float, lng: float}>  $points
     * @param  array{start: array{lat: float, lng: float}, end: array{lat: float, lng: float}}  $endpoints
     */
    private function summarizeGps(array $segments, array $points, array $endpoints): string
    {
        $parts = [];

        $km = $this->totalKm($segments);
        if ($km !== null) {
            $parts[] = rtrim(rtrim(number_format($km, 2, '.', ''), '0'), '.').' km';
        }

        $parts[] = number_format(count($points)).' điểm GPS';

        return implode(' · ', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private function totalKm(array $segments): ?float
    {
        foreach ($segments as $segment) {
            foreach (['tongKm', 'TotalKm', 'totalKm'] as $key) {
                $value = $segment[$key] ?? null;
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array{lat: float, lng: float}  $point
     */
    private function formatCoordinate(array $point): string
    {
        return number_format($point['lat'], 5, '.', '').','.number_format($point['lng'], 5, '.', '');
    }
}
