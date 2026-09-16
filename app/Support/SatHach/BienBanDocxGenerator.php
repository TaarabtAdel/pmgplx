<?php

namespace App\Support\SatHach;

use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class BienBanDocxGenerator
{
    public const TEMPLATE = 'templates/bien_ban_tong_hop.docx';

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

        $image = [
            'path' => $photo,
            'width' => '3cm',
            'height' => '4cm',
            'ratio' => false,
        ];

        foreach (["%ANH_CHAN_DUNG#{$i}", '%ANH_CHAN_DUNG', "ANH_CHAN_DUNG#{$i}"] as $macro) {
            $processor->setImageValue($macro, $image);
        }
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
