<?php

namespace App\Support\DaoTao;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DatNominatimReverseGeocode
{
    public function labelForCoordinate(float $lat, float $lng): ?string
    {
        $lookup = $this->lookupForCoordinate($lat, $lng);

        return $lookup['label'];
    }

    /**
     * @return array{
     *     lat: float,
     *     lng: float,
     *     label: string|null,
     *     display_name: string|null,
     *     address: array<string, string>
     * }
     */
    public function lookupForCoordinate(float $lat, float $lng): array
    {
        $cacheKey = sprintf(
            'dat.nominatim.%s.%s',
            number_format($lat, 4, '.', ''),
            number_format($lng, 4, '.', '')
        );

        /** @var array<string, mixed> $cached */
        $cached = Cache::remember($cacheKey, now()->addDay(), function () use ($lat, $lng): array {
            return $this->fetchLookup($lat, $lng);
        });

        return [
            'lat' => (float) ($cached['lat'] ?? $lat),
            'lng' => (float) ($cached['lng'] ?? $lng),
            'label' => is_string($cached['label'] ?? null) ? $cached['label'] : null,
            'display_name' => is_string($cached['display_name'] ?? null) ? $cached['display_name'] : null,
            'address' => is_array($cached['address'] ?? null)
                ? array_map(strval(...), $cached['address'])
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $lookup
     */
    public function displayLabelFromLookup(array $lookup): ?string
    {
        $label = trim((string) ($lookup['label'] ?? ''));
        if ($label !== '') {
            return $label;
        }

        $displayName = trim((string) ($lookup['display_name'] ?? ''));
        if ($displayName !== '') {
            return $this->shortenPlaceName($this->firstSegment($displayName));
        }

        return null;
    }

    public function composeRouteName(float $startLat, float $startLng, float $endLat, float $endLng): ?string
    {
        $startLabel = $this->labelForCoordinate($startLat, $startLng);
        $endLabel = $this->labelForCoordinate($endLat, $endLng);

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
     * @return array<string, mixed>
     */
    private function fetchLookup(float $lat, float $lng): array
    {
        $this->throttle();

        $baseUrl = rtrim((string) config('services.nominatim.base_url'), '/');
        $timeout = (int) config('services.nominatim.timeout', 10);

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('services.nominatim.user_agent'),
                'Accept-Language' => 'vi',
            ])
                ->acceptJson()
                ->timeout($timeout)
                ->get($baseUrl.'/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'accept-language' => 'vi',
                ]);
        } catch (RequestException $e) {
            throw new \RuntimeException('Nominatim reverse geocode thất bại: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException('Nominatim reverse geocode trả lỗi ('.$response->status().').');
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $response->json();

        return $this->buildLookup($payload, $lat, $lng);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function buildLookup(?array $payload, float $lat, float $lng): array
    {
        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];
        $normalizedAddress = [];

        foreach ($address as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text !== '') {
                $normalizedAddress[$key] = $text;
            }
        }

        $displayName = trim((string) ($payload['display_name'] ?? ''));

        return [
            'lat' => $lat,
            'lng' => $lng,
            'label' => $this->labelFromAddress($normalizedAddress, $displayName),
            'display_name' => $displayName !== '' ? $displayName : null,
            'address' => $normalizedAddress,
        ];
    }

    /**
     * @param  array<string, string>  $address
     */
    private function labelFromAddress(array $address, string $displayName = ''): ?string
    {
        foreach (['suburb', 'village', 'town', 'hamlet', 'locality', 'neighbourhood', 'city_district', 'municipality', 'county', 'city', 'road', 'state'] as $key) {
            $value = trim((string) ($address[$key] ?? ''));
            if ($value !== '') {
                return $this->shortenPlaceName($value);
            }
        }

        if ($displayName !== '') {
            return $this->shortenPlaceName($this->firstSegment($displayName));
        }

        return null;
    }

    private function throttle(): void
    {
        $lockKey = 'dat.nominatim.throttle';
        $lastRequest = Cache::get($lockKey);

        if (is_numeric($lastRequest)) {
            $elapsed = microtime(true) - (float) $lastRequest;
            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1_000_000));
            }
        }

        Cache::put($lockKey, microtime(true), now()->addSeconds(2));
    }

    private function shortenPlaceName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        foreach (['Phường ', 'Xã ', 'Quận ', 'Huyện ', 'Thành phố ', 'Thành Phố ', 'Tỉnh ', 'TP. '] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return trim(substr($name, strlen($prefix)));
            }
        }

        return $name;
    }

    private function firstSegment(string $value): string
    {
        $parts = preg_split('/[,;]/u', $value) ?: [];

        return trim((string) ($parts[0] ?? $value));
    }
}
