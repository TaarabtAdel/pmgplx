<?php

namespace App\Support\SatHach;

use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class BienBanDocxGenerator
{
    public const TEMPLATE = 'templates/bien_ban_tong_hop.docx';

    /** Kích thước khung ảnh chân dung trong biên bản (đơn vị: inch) */
    private const PORTRAIT_WIDTH_IN = 1.15;
    private const PORTRAIT_HEIGHT_IN = 1.45;

    /**
     * @param  array<string, mixed>  $thiSinh
     */
    public function generateOne(array $thiSinh, string $outputPath): string
    {
        return $this->generate([$thiSinh], $outputPath);
    }

    /**
     * @param  list<array<string, mixed>>  $thiSinh
     */
    public function generate(array $thiSinh, string $outputPath): string
    {
        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new \RuntimeException('Không tìm thấy file mẫu biên bản: '.$template);
        }

        if ($thiSinh === []) {
            throw new \InvalidArgumentException('Không có thí sinh để sinh biên bản.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        $workDir = dirname($outputPath).DIRECTORY_SEPARATOR.'photos-'.uniqid('', true);
        @mkdir($workDir, 0755, true);

        $converter = new ImageConverterService();
        $processor = new TemplateProcessor($template);

        try {
            $cloned = $processor->cloneBlock('bienban', count($thiSinh), true, true);
            if ($cloned === null) {
                throw new \RuntimeException('File mẫu thiếu khối ${bienban} … ${/bienban}.');
            }

            foreach ($thiSinh as $index => $row) {
                $i = $index + 1;
                try {
                    $this->fillCandidate($processor, $converter, $row, $i, $workDir);
                } catch (Throwable $e) {
                    report($e);
                    $this->fillErrorPage($processor, $converter, $row, $i, $workDir, $e->getMessage());
                }
            }

            $processor->saveAs($outputPath);
        } finally {
            $this->deleteDirectory($workDir);
        }

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file DOCX kết quả.');
        }

        return $outputPath;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fillCandidate(
        TemplateProcessor $processor,
        ImageConverterService $converter,
        array $row,
        int $i,
        string $workDir
    ): void {
        $values = [
            "SO_BAO_DANH#{$i}" => $this->cell($row['so_bao_danh'] ?? ''),
            "HO_VA_TEN#{$i}" => $this->cell($row['ho_va_ten'] ?? ''),
            "NGAY_SINH#{$i}" => $this->cell($row['ngay_sinh'] ?? ''),
            "SO_CMT#{$i}" => $this->cell($row['so_cmt'] ?? ''),
            "SO_HO_CHIEU#{$i}" => $this->cell($row['so_ho_chieu'] ?? ''),
            "NGAY_CAP_HC#{$i}" => $this->cell($row['ngay_cap_hc'] ?? ''),
            "NOI_CAP_HC#{$i}" => $this->cell($row['noi_cap_hc'] ?? ''),
            "HANG_GPLX#{$i}" => $this->cell($row['hang_gplx'] ?? ''),
            "HANG_GPLX_KL#{$i}" => $this->cell($row['hang_gplx'] ?? ''),
            "DIEM_LT_TOIDA#{$i}" => $this->cell($row['diem_lt_toida'] ?? XmlSatHachParser::diemLtToiDa((string) ($row['hang_gplx'] ?? ''))),
            "DIEM_LT_DAT#{$i}" => $this->cell($row['diem_lt_dat'] ?? '-'),
            "NHAN_XET_LT#{$i}" => $this->cell($row['nhan_xet_lt'] ?? ''),
            "DIEM_HINH_DAT#{$i}" => $this->cell($row['diem_hinh_dat'] ?? '-'),
            "NHAN_XET_HINH#{$i}" => $this->cell($row['nhan_xet_hinh'] ?? ''),
            "DIEM_DUONG_DAT#{$i}" => $this->cell($row['diem_duong_dat'] ?? '-'),
            "NHAN_XET_DUONG#{$i}" => $this->cell($row['nhan_xet_duong'] ?? ''),
            "KET_QUA_TEXT#{$i}" => $this->cell($row['ket_qua_text'] ?? ''),
            "NGAY_KY#{$i}" => $this->cell($row['ngay_ky'] ?? ''),
            "THANG_KY#{$i}" => $this->cell($row['thang_ky'] ?? ''),
            "NAM_KY#{$i}" => $this->cell($row['nam_ky'] ?? ''),
        ];

        foreach ($values as $key => $value) {
            $processor->setValue($key, $value);
        }

        $this->setPortrait($processor, $converter, $row, $i, $workDir);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fillErrorPage(
        TemplateProcessor $processor,
        ImageConverterService $converter,
        array $row,
        int $i,
        string $workDir,
        string $message
    ): void {
        $processor->setValue("HO_VA_TEN#{$i}", $this->cell($row['ho_va_ten'] ?? $row['ma_dk'] ?? ''));
        $processor->setValue("SO_BAO_DANH#{$i}", $this->cell($row['so_bao_danh'] ?? ''));
        $processor->setValue("NHAN_XET_LT#{$i}", 'Lỗi dữ liệu: '.$message);
        foreach ([
            "NGAY_SINH#{$i}", "SO_CMT#{$i}", "SO_HO_CHIEU#{$i}", "NGAY_CAP_HC#{$i}", "NOI_CAP_HC#{$i}",
            "HANG_GPLX#{$i}", "HANG_GPLX_KL#{$i}", "DIEM_LT_DAT#{$i}", "DIEM_HINH_DAT#{$i}", "DIEM_DUONG_DAT#{$i}",
            "NHAN_XET_HINH#{$i}", "NHAN_XET_DUONG#{$i}", "KET_QUA_TEXT#{$i}",
            "NGAY_KY#{$i}", "THANG_KY#{$i}", "NAM_KY#{$i}",
        ] as $key) {
            $processor->setValue($key, '');
        }
        $this->setPortrait($processor, $converter, $row, $i, $workDir);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function setPortrait(
        TemplateProcessor $processor,
        ImageConverterService $converter,
        array $row,
        int $i,
        string $workDir
    ): void {
        $photo = $converter->portraitPath(
            isset($row['anh_chan_dung_b64']) ? (string) $row['anh_chan_dung_b64'] : null,
            $workDir
        );

        // PhpWord's setImageValue 'ratio' option only supports two modes:
        //   ratio=true  -> letterbox (fits inside box, leaves blank margin if aspect differs)
        //   ratio=false -> stretch (fills box exactly, but distorts if aspect differs)
        // Neither is "crop to fill" (object-fit: cover). Real ID/passport photos rarely
        // match the box's exact aspect ratio, so we crop the photo ourselves first.
        $photo = $this->cropToFill($photo, $workDir, self::PORTRAIT_WIDTH_IN / self::PORTRAIT_HEIGHT_IN);

        $image = [
            'path' => $photo,
            'width' => self::PORTRAIT_WIDTH_IN.'in',
            'height' => self::PORTRAIT_HEIGHT_IN.'in',
            // Ratio no longer matters since the image is already cropped to the exact
            // target ratio, but 'false' avoids any residual letterbox rounding.
            'ratio' => false,
        ];

        foreach (["%ANH_CHAN_DUNG#{$i}", '%ANH_CHAN_DUNG', "ANH_CHAN_DUNG#{$i}"] as $macro) {
            $processor->setImageValue($macro, $image);
        }
    }

    /**
     * Crop an image to exactly match $targetRatio (width/height) — equivalent to CSS
     * `object-fit: cover`. Center-crops the long axis, biased toward the top on the
     * vertical axis (so portraits keep the face rather than centering on the torso).
     * Tries GD first, then Imagick, so it works if either extension is available.
     * Returns the path to a new temp PNG file, or the original path if neither
     * extension is available / the file can't be read (fails soft, never throws).
     */
    private function cropToFill(string $srcPath, string $workDir, float $targetRatio): string
    {
        $info = @getimagesize($srcPath);
        if ($info === false) {
            return $srcPath;
        }

        [$w, $h] = $info;
        $srcRatio = $w / $h;

        // Already matches (within rounding) — skip cropping entirely.
        if (abs($srcRatio - $targetRatio) < 0.005) {
            return $srcPath;
        }

        if ($srcRatio > $targetRatio) {
            // Image is relatively wider than the box -> trim left/right, keep full height.
            $cropH = $h;
            $cropW = (int) round($h * $targetRatio);
            $srcX = (int) round(($w - $cropW) / 2);
            $srcY = 0;
        } else {
            // Image is relatively taller than the box -> trim top/bottom, keep full width.
            $cropW = $w;
            $cropH = (int) round($w / $targetRatio);
            $srcX = 0;
            // Bias the crop toward the top so we keep the face, not the chest/torso.
            $srcY = (int) round(($h - $cropH) * 0.25);
        }

        $outPath = $workDir.DIRECTORY_SEPARATOR.'portrait_'.uniqid('', true).'.png';

        if (extension_loaded('gd')) {
            [, , $type] = $info;
            $srcImg = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
                IMAGETYPE_PNG => @imagecreatefrompng($srcPath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : null,
                default => null,
            };

            if ($srcImg !== null && $srcImg !== false) {
                $dst = imagecreatetruecolor($cropW, $cropH);
                imagecopy($dst, $srcImg, 0, 0, $srcX, $srcY, $cropW, $cropH);
                imagepng($dst, $outPath);
                imagedestroy($srcImg);
                imagedestroy($dst);

                return $outPath;
            }
        }

        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick($srcPath);
                $img->cropImage($cropW, $cropH, $srcX, $srcY);
                $img->setImageFormat('png');
                $img->writeImage($outPath);
                $img->clear();

                return $outPath;
            } catch (Throwable $e) {
                report($e);
            }
        }

        // Không có GD lẫn Imagick (hoặc crop thất bại) -> dùng ảnh gốc, chấp nhận
        // quay lại lỗi viền trắng/méo thay vì làm hỏng cả biên bản.
        return $srcPath;
    }

    private function cell(mixed $value): string
    {
        $text = trim((string) $value);
        if (in_array(mb_strtolower($text), ['null', 'undefined'], true)) {
            return '';
        }

        return $text;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}