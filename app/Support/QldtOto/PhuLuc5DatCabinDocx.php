<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;

/** Phụ lục 5 — báo cáo số lượng HV hoàn thành quy định thời gian / quãng đường DAT. */
final class PhuLuc5DatCabinDocx
{
    private const TEMPLATE = 'templates/oto/phu-luc-5-dat-cabin.goc.docx';

    /**
     * @param  array{B: int, B.01: int, C1: int, tong: int}  $dem
     */
    public static function generate(
        string $maKhoaHoc,
        Carbon $tuNgay,
        Carbon $denNgay,
        array $dem,
        string $outputPath
    ): void {
        $src = resource_path(self::TEMPLATE);
        if (! is_file($src)) {
            throw new \RuntimeException('Chưa có mẫu: '.self::TEMPLATE);
        }

        @mkdir(dirname($outputPath), 0755, true);
        if (! copy($src, $outputPath)) {
            throw new \RuntimeException('Không copy được mẫu Word.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new \RuntimeException('Không mở được file docx.');
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml);
        $xpath = DocxTextReplacer::xpath($dom);
        $body = $xpath->query('//w:body')->item(0);
        if (! $body instanceof \DOMNode) {
            throw new \RuntimeException('document.xml không hợp lệ.');
        }

        $khoangNgay = sprintf(
            '( Từ ngày: %s đến ngày: %s)',
            self::ngaySlashes($tuNgay),
            self::ngaySlashes($denNgay)
        );

        foreach ($xpath->query('.//w:p', $body) ?: [] as $p) {
            $chu = $p->textContent ?? '';
            if (str_contains($chu, 'Từ') && str_contains($chu, 'đến') && str_contains($chu, 'ngày')) {
                DocxTextReplacer::setParagraphText($xpath, $p, $khoangNgay);
                break;
            }
        }

        self::dienBang($xpath, $body, $maKhoaHoc, $dem);

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();
    }

    /**
     * @param  array{B: int, B.01: int, C1: int, tong: int}  $dem
     */
    private static function dienBang(\DOMXPath $xpath, \DOMNode $body, string $maKhoa, array $dem): void
    {
        $tbl = null;
        foreach ($xpath->query('.//w:tbl', $body) ?: [] as $candidate) {
            $header = $candidate->textContent ?? '';
            if (str_contains($header, 'Mã khóa học') && str_contains($header, 'số tự động')) {
                $tbl = $candidate;
                break;
            }
        }
        if (! $tbl instanceof \DOMNode) {
            throw new \RuntimeException('Không tìm thấy bảng phụ lục 5 trong mẫu Word.');
        }

        $rows = iterator_to_array($xpath->query('w:tr', $tbl) ?: []);
        if (count($rows) < 3) {
            throw new \RuntimeException('Bảng mẫu không đủ dòng.');
        }

        $dataCells = iterator_to_array($xpath->query('w:tc', $rows[1]) ?: []);
        if (count($dataCells) < 5) {
            throw new \RuntimeException('Dòng dữ liệu không đủ cột.');
        }

        DocxTextReplacer::setCellText($xpath, $dataCells[0], '1');
        DocxTextReplacer::setCellText($xpath, $dataCells[1], $maKhoa);
        DocxTextReplacer::setCellText($xpath, $dataCells[2], self::so($dem['B'] ?? 0));
        DocxTextReplacer::setCellText($xpath, $dataCells[3], self::so($dem['B.01'] ?? 0));
        DocxTextReplacer::setCellText($xpath, $dataCells[4], self::so($dem['C1'] ?? 0));

        $totalCells = iterator_to_array($xpath->query('w:tc', $rows[2]) ?: []);
        if (count($totalCells) >= 2) {
            DocxTextReplacer::setCellText($xpath, $totalCells[0], 'Tổng');
            DocxTextReplacer::setCellText($xpath, $totalCells[1], self::so($dem['tong'] ?? 0));
        }
    }

    private static function so(int $n): string
    {
        return $n > 0 ? (string) $n : '';
    }

    private static function ngaySlashes(Carbon $ngay): string
    {
        return $ngay->format('d/m/Y');
    }

    public static function tenFile(string $maKhoa): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $maKhoa) ?: 'khoa';

        return 'phu-luc-5-dat-'.$safe.'-'.now()->format('Ymd-His').'.docx';
    }
}