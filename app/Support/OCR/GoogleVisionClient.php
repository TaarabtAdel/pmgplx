<?php

namespace App\Support\OCR;

use Illuminate\Support\Facades\Http;

/** Google Cloud Vision — DOCUMENT_TEXT_DETECTION cho một ảnh. API key chỉ nằm ở server (.env). */
final class GoogleVisionClient
{
    private const ENDPOINT = 'https://vision.googleapis.com/v1/images:annotate';

    /**
     * @param  string  $base64  nội dung ảnh base64 (không kèm tiền tố data:)
     * @return array{text: string, confidence: float|null}
     */
    public static function detectText(string $base64): array
    {
        $key = (string) config('services.google_vision.key');
        if ($key === '') {
            throw new \RuntimeException('Chưa cấu hình GOOGLE_VISION_API_KEY trong .env.');
        }

        $res = Http::timeout(30)->post(self::ENDPOINT.'?key='.urlencode($key), [
            'requests' => [[
                'image' => ['content' => $base64],
                'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                'imageContext' => ['languageHints' => ['vi', 'en']],
            ]],
        ]);

        $data = $res->json() ?? [];
        if (! $res->successful()) {
            throw new \RuntimeException($data['error']['message'] ?? 'Google Vision lỗi HTTP '.$res->status());
        }

        $r = $data['responses'][0] ?? [];
        if (isset($r['error']['message'])) {
            throw new \RuntimeException($r['error']['message']);
        }

        $confs = [];
        foreach ($r['fullTextAnnotation']['pages'] ?? [] as $page) {
            foreach ($page['blocks'] ?? [] as $block) {
                if (isset($block['confidence']) && is_numeric($block['confidence'])) {
                    $confs[] = (float) $block['confidence'];
                }
            }
        }

        return [
            'text' => (string) ($r['fullTextAnnotation']['text'] ?? ''),
            'confidence' => $confs !== [] ? array_sum($confs) / count($confs) : null,
        ];
    }
}
