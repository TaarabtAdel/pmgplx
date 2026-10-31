<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Thông báo thời gian tập trung học thực hành lái xe — mẫu thong-bao-tap-trung-thuc-hanh.goc.docx. */
final class ThongBaoTapTrungThucHanhDocx
{
    public const GOC = 'templates/oto/thong-bao-tap-trung-thuc-hanh.goc.docx';

    public const DOCX = 'templates/oto/thong-bao-tap-trung-thuc-hanh.docx';

    public const HAU_TO_SO_TB = '/TB-TTML';

    public const DIA_DIEM_MAC_DINH = 'Sân tập lái Trung tâm GDNN Mạnh Linh; địa chỉ Km6+800 Điện Biên Phủ, P. Đông Hà, Quảng Trị';

    public const NOI_DUNG_MAC_DINH = 'Quán triệt quy chế của Trung tâm, nhận giáo viên chủ nhiệm và học bài tập đầu tiên số nóng, số nguội.';

    /** @var list<string> */
    public const BUOI = ['Buổi sáng', 'Buổi chiều', 'Buổi tối'];

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::GOC), resource_path(self::DOCX), [
            '365/TB-TTML' => '${SO_TB}',
            'Quảng Trị, ngày 25 tháng 06 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'hạng B (Số sàn)  Khóa 53' => 'hạng ${HANG} Khóa ${KHOA_SO}',
            'hạng B (Số sàn) Khóa 53' => 'hạng ${HANG} Khóa ${KHOA_SO}',
            'Buổi sáng 06h ngày 02/07/2026 (Thứ 5)' => '${THOI_GIAN}',
            self::DIA_DIEM_MAC_DINH => '${DIA_DIEM}',
            self::NOI_DUNG_MAC_DINH => '${NOI_DUNG}',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    /** «Buổi sáng 06h ngày 02/07/2026 (Thứ 5)». */
    public static function thoiGian(string $buoi, string $gio, string $ngay): string
    {
        $d = Carbon::parse($ngay);
        $thu = $d->dayOfWeek === Carbon::SUNDAY ? 'Chủ nhật' : 'Thứ '.($d->dayOfWeek + 1);

        return trim($buoi.' '.$gio).' ngày '.$d->format('d/m/Y').' ('.$thu.')';
    }

    /**
     * @param  array{so_tb: string, ngay_thong_bao: string, hang: string, khoa_so: string, buoi: string, gio: string, ngay_tap_trung: string, dia_diem: string, noi_dung: string}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-thong-bao-tap-trung-thuc-hanh-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $values = [
            'SO_TB' => $data['so_tb'].self::HAU_TO_SO_TB,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_thong_bao'])),
            'HANG' => $data['hang'],
            'KHOA_SO' => $data['khoa_so'],
            'THOI_GIAN' => self::thoiGian($data['buoi'], $data['gio'], $data['ngay_tap_trung']),
            'DIA_DIEM' => $data['dia_diem'],
            'NOI_DUNG' => $data['noi_dung'],
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file thông báo.');
        }

        return $outputPath;
    }
}
