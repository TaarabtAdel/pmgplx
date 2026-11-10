<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Biên bản xác nhận đã được phổ biến nội quy, quy chế, chương trình đào tạo. */
final class BienBanXacNhanPhoBienNoiQuyDocx
{
    public const GOC = 'templates/oto/bien-ban-xac-nhan-pho-bien-noi-quy-hoc-tap.goc.docx';

    public const DOCX = 'templates/oto/bien-ban-xac-nhan-pho-bien-noi-quy-hoc-tap.docx';

    public const DIA_DIEM_MAC_DINH = 'phòng học lý thuyết – 04 Lê Thế Hiếu – Phường Đông Hà – Quảng Trị';

    public const TEN_DDT_MAC_DINH = 'Hoàng Ngọc Hà';

    /** @var list<string> */
    public const XUNG = ['Ông', 'Bà'];

    public static function lopMoTaMacDinh(string $hang): string
    {
        $hang = trim($hang);
        if (preg_match('/^(\S+)\s*\((.+)\)\s*$/u', $hang, $m)) {
            return $m[1].' - '.$m[2];
        }

        return $hang;
    }

    public static function hangHoaTuMa(?string $hangGplx): string
    {
        $code = mb_strtoupper(trim((string) $hangGplx));

        return $code !== '' ? $code : 'B';
    }

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::GOC), resource_path(self::DOCX), [
            'Quảng Trị, ngày …. tháng … năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'ĐÀO TẠO LÁI XE Ô TÔ HẠNG B CỦA' => 'ĐÀO TẠO LÁI XE Ô TÔ HẠNG ${HANG_HOA} CỦA',
            'Hôm nay, vào lúc … giờ 00 phút ngày …/…/2026 tại phòng học lý thuyết – 04 Lê Thế Hiếu – Phường Đông Hà – Quảng Trị;' => 'Hôm nay, vào lúc ${GIO} giờ 00 phút ngày ${NGAY_HOP} tại ${DIA_DIEM};',
            'Chúng tôi đại diện cho Lớp B -Số tự động/Số sàn Khóa K… gồm:' => 'Chúng tôi đại diện cho Lớp ${LOP_MO_TA} Khóa ${KHOA_SO} gồm:',
            '1. ………………………………………………… Sinh ngày: ……/……./……..' => '${DD_LINE_1}',
            '2. ………………………………………………… Sinh ngày: ……/……./……' => '${DD_LINE_2}',
            '3. ………………………………………………… Sinh ngày: ……/……./………' => '${DD_LINE_3}',
            'Xác nhận đã được Ông (bà):………………………..– Đại diện Phòng Đào tạo Trung tâm GDNN Mạnh Linh phổ biến các nội dung bao gồm:' => 'Xác nhận đã được ${XUNG_DDT} (bà): ${TEN_DDT} – Đại diện Phòng Đào tạo Trung tâm GDNN Mạnh Linh phổ biến các nội dung bao gồm:',
            'Chương trình đào tạo lái xe hạng B của Trung tâm;' => 'Chương trình đào tạo lái xe hạng ${HANG_RIENG} của Trung tâm;',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    public static function dongDaiDien(int $stt, string $hoTen, string $ngaySinh): string
    {
        return sprintf('%d. %s Sinh ngày: %s', $stt, trim($hoTen), trim($ngaySinh));
    }

    /**
     * @param  array{
     *     ngay_lap: string,
     *     gio: string,
     *     ngay_hop: string,
     *     dia_diem: string,
     *     lop_mo_ta: string,
     *     khoa_so: string,
     *     hang_hoa: string,
     *     hang_rieng: string,
     *     xung_ddt: string,
     *     ten_ddt: string,
     *     dai_dien: list<array{ho_ten: string, ngay_sinh: string}>,
     * }  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-bien-ban-pho-bien-noi-quy-template');
        }

        $daiDien = array_values($data['dai_dien']);
        if (count($daiDien) !== 3) {
            throw new \InvalidArgumentException('Cần đúng 3 học viên đại diện.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $values = [
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_lap'])),
            'GIO' => $data['gio'],
            'NGAY_HOP' => Carbon::parse($data['ngay_hop'])->format('d/m/Y'),
            'DIA_DIEM' => $data['dia_diem'],
            'LOP_MO_TA' => $data['lop_mo_ta'],
            'KHOA_SO' => $data['khoa_so'],
            'HANG_HOA' => $data['hang_hoa'],
            'HANG_RIENG' => $data['hang_rieng'],
            'XUNG_DDT' => $data['xung_ddt'],
            'TEN_DDT' => $data['ten_ddt'],
        ];

        foreach ($daiDien as $i => $dd) {
            $values['DD_LINE_'.($i + 1)] = self::dongDaiDien($i + 1, $dd['ho_ten'], $dd['ngay_sinh']);
        }

        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc((string) $value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file biên bản.');
        }

        return $outputPath;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
