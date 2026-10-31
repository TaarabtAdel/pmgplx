<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Quyết định bảo lưu kết quả học tập — mẫu quyet-dinh-bao-luu.doc (một học viên / quyết định). */
final class QuyetDinhBaoLuuDocx
{
    public const DOC = 'templates/oto/quyet-dinh-bao-luu.doc';

    public const DOCX = 'templates/oto/quyet-dinh-bao-luu.docx';

    public const LY_DO_MAC_DINH = 'Học viên bận việc không thể sắp xếp lịch học thực hành lái xe';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            '487/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, ngày 10 tháng 08 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'hạng B (Số sàn) Khóa 53' => 'hạng ${HANG} Khóa ${KHOA_SO}',
            'hạng B (Số sàn) khóa 53' => 'hạng ${HANG} khóa ${KHOA_SO}',
            'Nguyễn Công Sinh' => '${HO_TEN}',
            '30/11/1999' => '${NGAY_SINH}',
            'Học viên bận việc không thể sắp xếp lịch học thực hành lái xe' => '${LY_DO}',
            'lớp Bk53' => 'lớp ${TEN_LOP}',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boBangRongCuoi($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
            self::canGiuaDongNgay($xpath);
        });
    }

    /** Dòng ngày dùng dấu cách để đẩy sang phải — ngày dài là xuống dòng; bỏ dấu cách, căn giữa như dòng quốc hiệu. */
    private static function canGiuaDongNgay(\DOMXPath $xpath): void
    {
        $p = $xpath->query('//w:p[contains(., "Quảng Trị, ngày")]')->item(0);
        if (! $p instanceof \DOMElement) {
            return;
        }
        foreach ($xpath->query('.//w:t', $p) ?: [] as $t) {
            if (trim($t->textContent) === '') {
                $t->textContent = '';
            } else {
                $t->textContent = ltrim($t->textContent);
                break;
            }
        }

        $pPr = $xpath->query('w:pPr', $p)->item(0);
        if (! $pPr instanceof \DOMElement) {
            $pPr = $p->ownerDocument->createElementNS(DocxTextReplacer::W_NS, 'w:pPr');
            $p->insertBefore($pPr, $p->firstChild);
        }
        foreach ($xpath->query('w:jc|w:ind', $pPr) ?: [] as $old) {
            $pPr->removeChild($old);
        }
        $jc = $p->ownerDocument->createElementNS(DocxTextReplacer::W_NS, 'w:jc');
        $jc->setAttributeNS(DocxTextReplacer::W_NS, 'w:val', 'center');
        $rPr = $xpath->query('w:rPr', $pPr)->item(0);
        $pPr->insertBefore($jc, $rPr);
    }

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, ho_ten: string, ngay_sinh: string, hang: string, khoa_so: string, ten_lop: string, ly_do: string}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-bao-luu-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_quyet_dinh'])),
            'HO_TEN' => $data['ho_ten'],
            'NGAY_SINH' => $data['ngay_sinh'],
            'HANG' => $data['hang'],
            'KHOA_SO' => $data['khoa_so'],
            'TEN_LOP' => $data['ten_lop'],
            'LY_DO' => $data['ly_do'],
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định bảo lưu.');
        }

        return $outputPath;
    }
}
