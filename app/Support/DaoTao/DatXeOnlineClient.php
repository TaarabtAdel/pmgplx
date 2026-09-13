<?php

namespace App\Support\DaoTao;

use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class DatXeOnlineClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSegments(Carbon $ngayBatDau, Carbon $ngayKetThuc, string $bienSo, string $bearerToken): array
    {
        $baseUrl = rtrim((string) config('services.xeonline.base_url'), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('Chưa cấu hình XEONLINE_API_BASE_URL.');
        }

        $bienSo = trim($bienSo);
        if ($bienSo === '') {
            throw new \InvalidArgumentException('Thiếu biển số xe.');
        }

        try {
            $response = Http::withToken($bearerToken)
                ->acceptJson()
                ->timeout((int) config('services.xeonline.timeout', 30))
                ->get($baseUrl.'/api/XeOnline', [
                    'ngaybatdau' => $ngayBatDau->format('Y-m-d\TH:i:s'),
                    'ngayketthuc' => $ngayKetThuc->format('Y-m-d\TH:i:s'),
                    'bienso' => $bienSo,
                ]);
        } catch (RequestException $e) {
            $message = $e->response?->json('Message') ?? $e->getMessage();
            throw new \RuntimeException('Gọi API XeOnline thất bại: '.$message, 0, $e);
        }

        if (! $response->successful()) {
            $message = $response->json('Message') ?? $response->body();
            throw new \RuntimeException('API XeOnline trả lỗi ('.$response->status().'): '.$message);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        if ($payload !== [] && array_is_list($payload)) {
            /** @var list<array<string, mixed>> $payload */
            return $payload;
        }

        /** @var array<string, mixed> $payload */
        return [$payload];
    }
}
