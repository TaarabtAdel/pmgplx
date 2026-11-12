<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Support\PMGPLX\DuongDanAnhPmGplx;
use App\Support\PMGPLX\Jp2PhotoConverter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AnhHocVienController extends Controller
{
    public function show(Request $request): Response|SymfonyResponse
    {
        $token = (string) $request->query('p', '');
        $duongDanAnh = DuongDanAnhPmGplx::decodePath($token);

        if ($duongDanAnh === null) {
            return $this->placeholder();
        }

        $file = DuongDanAnhPmGplx::resolveReadablePath($duongDanAnh);
        if ($file === null) {
            return $this->placeholder();
        }

        $binary = file_get_contents($file);
        if ($binary === false || $binary === '') {
            return $this->placeholder();
        }

        if (Jp2PhotoConverter::isJp2($binary)) {
            $png = Jp2PhotoConverter::toPngBinary($binary);
            if ($png !== null) {
                return response($png, 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }

            return $this->placeholder();
        }

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response($binary, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function placeholder(): Response
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
  <rect width="50" height="50" fill="#e9ecef"/>
  <text x="25" y="28" text-anchor="middle" font-family="sans-serif" font-size="9" fill="#6c757d">N/A</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store',
        ]);
    }
}
