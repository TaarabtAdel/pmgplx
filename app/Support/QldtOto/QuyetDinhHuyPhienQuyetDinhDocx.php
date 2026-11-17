<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

/**
 * Quyết định huỷ phiên kèm danh sách phiên huỷ — dựng bằng PhpWord (mẫu tự sinh, có chữ ký).
 */
final class QuyetDinhHuyPhienQuyetDinhDocx
{
    private const FONT = 'Times New Roman';

    public const DOCX = 'templates/oto/quyet-dinh-huy-phien-kem-danh-sach.docx';

    /**
     * Sinh file mẫu docx (placeholder) để kiểm tra / lưu trong repo.
     */
    public static function buildTemplate(): string
    {
        $path = resource_path(self::DOCX);
        @mkdir(dirname($path), 0755, true);
        self::generate([
            'so_qd' => '…',
            'ngay_quyet_dinh' => now()->format('Y-m-d'),
            'phien' => [
                [
                    'ten_khoa' => 'Khóa mẫu',
                    'ho_ten_gv' => 'Nguyễn Văn A',
                    'ho_ten' => 'Trần Văn B',
                    'ma_hv' => '44007-20260101-000000',
                    'ma_phien' => 'uuid-mau',
                    'phuong_an' => BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH,
                    'ly_do' => 'Lý do mẫu',
                ],
            ],
        ], $path);

        return $path;
    }

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, phien: list<array{ten_khoa: string, ho_ten_gv: string, ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $phien = array_values($data['phien']);
        if ($phien === []) {
            throw new \InvalidArgumentException('Chưa có dòng phiên nào trong danh sách.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $word = new PhpWord();
        $word->setDefaultFontName(self::FONT);
        $word->setDefaultFontSize(13);

        $section = $word->addSection([
            'marginLeft' => 1134,
            'marginRight' => 850,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);

        $bold = ['bold' => true];
        $center = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $justify = ['alignment' => Jc::BOTH, 'spaceAfter' => 120];

        $head = $section->addTable(['borderSize' => 0]);
        $head->addRow();
        $left = $head->addCell(4500);
        $left->addText('SỞ GIÁO DỤC VÀ ĐÀO TẠO', $bold, $center);
        $left->addText('TRUNG TÂM GDNN MẠNH LINH', $bold, $center);
        $right = $head->addCell(5000);
        $right->addText('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', $bold, $center);
        $right->addText('Độc lập - Tự do - Hạnh phúc', [], $center);
        $right->addTextBreak(1);
        $ngay = BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_quyet_dinh']));
        $right->addText('Quảng Trị, '.$ngay, [], $center);

        $section->addTextBreak(1);
        $section->addText('QUYẾT ĐỊNH', array_merge($bold, ['size' => 14]), $center);
        $soQd = trim($data['so_qd']).QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;
        $section->addText('Số: '.$soQd, $bold, $center);
        $section->addText('V/v huỷ phiên học lái xe không đúng quy định', $bold, $center);
        $section->addTextBreak(1);

        $section->addText(
            'GIÁM ĐỐC TRUNG TÂM GIÁO DỤC NGHỀ NGHIỆP MẠNH LINH',
            $bold,
            $center
        );
        $section->addTextBreak(1);
        $section->addText(
            'Căn cứ báo cáo phiên học lỗi của Phòng đào tạo / bộ phận DAT;',
            [],
            $justify
        );
        $section->addText(
            'Căn cứ quy định quản lý đào tạo lái xe tại Trung tâm;',
            [],
            $justify
        );
        $section->addTextBreak(1);
        $section->addText('QUYẾT ĐỊNH:', $bold, $justify);
        $section->addText(
            'Điều 1. Huỷ các phiên học lái xe không đúng quy định, cụ thể tại danh sách sau:',
            [],
            $justify
        );

        $cols = [500, 1100, 1400, 1500, 1600, 2000, 900, 2200];
        $headers = ['STT', 'Khóa học', 'Giáo viên', 'Họ và tên', 'Mã HV', 'Mã phiên', 'P.án', 'Lý do'];
        $tbl = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);
        $tbl->addRow();
        foreach ($headers as $i => $label) {
            $cell = $tbl->addCell($cols[$i], ['valign' => VerticalJc::CENTER]);
            $cell->addText($label, $bold, $center);
        }
        foreach ($phien as $i => $row) {
            $tbl->addRow();
            $vals = [
                (string) ($i + 1),
                $row['ten_khoa'] ?? '',
                $row['ho_ten_gv'] ?? '',
                $row['ho_ten'],
                $row['ma_hv'],
                $row['ma_phien'],
                $row['phuong_an'],
                $row['ly_do'],
            ];
            foreach ($vals as $j => $text) {
                $cell = $tbl->addCell($cols[$j], ['valign' => VerticalJc::CENTER]);
                $cell->addText($text, ['size' => 11], $j === 7 ? ['alignment' => Jc::BOTH] : ['alignment' => Jc::CENTER]);
            }
        }

        $section->addTextBreak(1);
        $section->addText(
            'Điều 2. Phòng đào tạo, bộ phận DAT và các giáo viên có liên quan chịu trách nhiệm thi hành quyết định này.',
            [],
            $justify
        );

        $section->addTextBreak(2);
        $sign = $section->addTable(['borderSize' => 0]);
        $sign->addRow();
        $sign->addCell(4500);
        $sRight = $sign->addCell(5000);
        $sRight->addText('GIÁM ĐỐC', $bold, $center);
        $sRight->addTextBreak(3);
        $sRight->addText('(Ký, ghi rõ họ tên)', ['italic' => true, 'size' => 11], $center);

        IOFactory::createWriter($word, 'Word2007')->save($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định huỷ phiên.');
        }

        return $outputPath;
    }
}