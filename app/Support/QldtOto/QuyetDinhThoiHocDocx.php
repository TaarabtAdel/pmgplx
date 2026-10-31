<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Quyết định cho thôi học — mẫu quyet-dinh-cho-thoi-hoc.doc (một học viên / quyết định). */
final class QuyetDinhThoiHocDocx
{
    public const DOC = 'templates/oto/quyet-dinh-cho-thoi-hoc.doc';

    public const DOCX = 'templates/oto/quyet-dinh-cho-thoi-hoc.docx';

    public const LY_DO_MAC_DINH = 'Học viên không hoàn thành thời gian, quãng đường học thực hành theo quy định.';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            '487/QĐ-TTML' => '${SO_QD}',
            'Quảng Trị, ngày 10 tháng 08 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'đối với 01 học viên B-K53' => 'đối với 01 học viên ${LOP_TIEU_DE}',
            'giáo viên chủ nhiệm Nguyễn Quang Năm' => 'giáo viên chủ nhiệm ${GVCN}',
            'Nguyễn Công Sinh' => '${HO_TEN}',
            '30/11/1999' => '${NGAY_SINH}',
            'Phường Nam Đông Hà, Tỉnh Quảng Trị' => '${NOI_CU_TRU}',
            'hạng B Khóa 53' => 'hạng ${HANG} Khóa ${KHOA_SO}',
            'Học viên không hoàn thành thời gian, quãng đường học thực hành theo quy định.' => '${LY_DO}',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boBangRongCuoi($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, ho_ten: string, ngay_sinh: string, noi_cu_tru: string, hang: string, khoa_so: string, lop_tieu_de: string, gvcn: string, ly_do: string}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-cho-thoi-hoc-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_quyet_dinh'])),
            'LOP_TIEU_DE' => $data['lop_tieu_de'],
            'GVCN' => $data['gvcn'],
            'HO_TEN' => $data['ho_ten'],
            'NGAY_SINH' => $data['ngay_sinh'],
            'NOI_CU_TRU' => $data['noi_cu_tru'],
            'HANG' => $data['hang'],
            'KHOA_SO' => $data['khoa_so'],
            'LY_DO' => $data['ly_do'],
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định cho thôi học.');
        }

        return $outputPath;
    }
}
