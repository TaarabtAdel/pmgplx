<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Quyết định hoàn thành khoá học (chỉ trang quyết định — danh sách kèm theo xuất riêng file Excel).
 * Mẫu: quyet-dinh-hoan-thanh-khoa-hoc.doc → docx (biến ${SO_QD}, ${DS_NHOM}, …).
 */
final class QuyetDinhHoanThanhKhoaHocDocx
{
    public const DOC = 'templates/oto/quyet-dinh-hoan-thanh-khoa-hoc.doc';

    public const DOCX = 'templates/oto/quyet-dinh-hoan-thanh-khoa-hoc.docx';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            '743/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, Ngày 18 tháng 10 năm 2026' => 'Quảng Trị, ${NGAY_DONG}',
            'cho 56 học viên Hạng B (Số sàn) khóa 55 và 01 học viên Hạng B (Số sàn) khóa 54' => 'cho ${DS_NHOM}',
            'hoàn thành lớp học đào tạo lái xe hạng B (Số sàn) khóa 54' => 'hoàn thành lớp học đào tạo lái xe hạng ${HANG_CHINH} khóa ${KHOA_CHINH}',
        ], function (\DOMXPath $xpath): void {
            self::doiNguoiKy($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    /** Khối ký: «KT.GIÁM ĐỐC TRUNG TÂM / PHÓ GIÁM ĐỐC». */
    private static function doiNguoiKy(\DOMXPath $xpath): void
    {
        $p = null;
        foreach ($xpath->query('//w:tbl//w:tc/w:p') as $node) {
            if (trim($node->textContent) === 'GIÁM ĐỐC TRUNG TÂM') {
                $p = $node;
                break;
            }
        }
        if (! $p instanceof \DOMElement) {
            throw new \RuntimeException('Không thấy dòng «GIÁM ĐỐC TRUNG TÂM» ở khối ký.');
        }

        $pho = $p->cloneNode(true);
        DocxTextReplacer::setParagraphText($xpath, $p, 'KT.GIÁM ĐỐC TRUNG TÂM');
        DocxTextReplacer::setParagraphText($xpath, $pho, 'PHÓ GIÁM ĐỐC');
        $p->parentNode->insertBefore($pho, $p->nextSibling);

        $sau = $pho->nextSibling;
        if ($sau instanceof \DOMElement && $sau->localName === 'p' && trim($sau->textContent) === '') {
            $sau->parentNode->removeChild($sau);
        }
    }

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, khoa: list<array{hang: string, khoa_so: string, ten_ngan: string, hoc_vien: list<mixed>}>}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-hoan-thanh-khoa-hoc-template');
        }
        $khoa = array_values(array_filter($data['khoa'], fn ($k) => $k['hoc_vien'] !== []));
        if ($khoa === []) {
            throw new \InvalidArgumentException('Chưa chọn học viên nào.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $nhom = array_map(
            fn ($k) => sprintf('%02d', count($k['hoc_vien'])).' học viên Hạng '.$k['hang'].' khóa '.$k['khoa_so'],
            $khoa
        );
        $dsNhom = count($nhom) > 1 ? implode(', ', array_slice($nhom, 0, -1)).' và '.end($nhom) : $nhom[0];

        $processor = new TemplateProcessor($template);
        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay),
            'DS_NHOM' => $dsNhom,
            'HANG_CHINH' => $khoa[0]['hang'],
            'KHOA_CHINH' => $khoa[0]['khoa_so'],
        ];
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định hoàn thành.');
        }

        return $outputPath;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}