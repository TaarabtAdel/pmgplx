<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

/**
 * «Danh sách thí sinh dự sát hạch» — Báo cáo 2, theo bố cục file ds bc2.doc (A4 ngang).
 * Mẫu gốc là file xuất từ phần mềm báo cáo (mỗi ô là khung định vị tuyệt đối) nên dựng lại bằng PhpWord.
 */
class DanhSachBc2DocxGenerator
{
    private const FONT = 'Times New Roman';

    private const NGUOI_KY = 'TRƯƠNG HOÀNG LINH';

    /** Độ rộng cột (twip), tổng ≈ chiều ngang vùng in A4 ngang lề 1cm. */
    private const COT = [600, 2700, 1250, 1400, 3700, 700, 700, 700, 900, 1300, 1000];

    /**
     * @param  array{hang_bc2: string, khoa_bc2: string, thoi_gian_dt: string, ngay_kg: string, ngay_bg: string, ngay_bao_cao: string}  $form
     * @param  list<array{ho_ten: string, ngay_sinh: string, so_cmnd: string, noi_dk_ho_khau: string, giay_cnsk: bool, da_co_gplx: string, so_nam_lx: string, so_km: string, so_ccn: string, ghi_chu: string}>  $hocVien
     */
    public function generate(array $form, array $hocVien, string $outputPath): string
    {
        if ($hocVien === []) {
            throw new \InvalidArgumentException('Không có thí sinh để xuất danh sách.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $word = new PhpWord();
        $word->setDefaultFontName(self::FONT);
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginLeft' => 567,
            'marginRight' => 567,
            'marginTop' => 567,
            'marginBottom' => 567,
            'footerHeight' => 300,
        ]);

        $section->addFooter()->addPreserveText(
            'Trang {PAGE}/{NUMPAGES}',
            ['size' => 11],
            ['alignment' => Jc::CENTER]
        );

        $this->dauTrang($section, $form);
        $this->bangThiSinh($section, $hocVien);
        $this->chuKy($section, (string) $form['ngay_bao_cao']);

        IOFactory::createWriter($word, 'Word2007')->save($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file danh sách BC2.');
        }

        return $outputPath;
    }

    private function dauTrang($section, array $form): void
    {
        $p0 = ['spaceAfter' => 0, 'spaceBefore' => 0];
        $center = $p0 + ['alignment' => Jc::CENTER];
        $bold = ['bold' => true];

        $tbl = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 30]);
        $tbl->addRow();

        $left = $tbl->addCell(4300, ['valign' => VerticalJc::CENTER]);
        $left->addText('Sở Xây dựng Quảng Trị', $bold, $center);
        $left->addText('Trung Tâm Giáo Dục Nghề Nghiệp Mạnh Linh', $bold, $center);

        $mid = $tbl->addCell(7000);
        $mid->addText('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['size' => 11], $center);
        $mid->addText('Độc lập - Tự do - Hạnh phúc', [], $center);
        $mid->addTextBreak(1, [], $p0);
        $mid->addText('DANH SÁCH THÍ SINH DỰ SÁT HẠCH', ['bold' => true, 'size' => 11], $center);
        $mid->addTextBreak(1, [], $p0);
        $line = $mid->addTextRun($center);
        $line->addText('Cấp giấy phép lái xe hạng:     ', $bold);
        $line->addText((string) $form['hang_bc2']);
        $line->addText('          Khóa:     ', $bold);
        $line->addText((string) $form['khoa_bc2']);

        $right = $tbl->addCell(4400);
        $right->addText('BÁO CÁO 2', $bold, $center);
        $tg = $right->addTextRun($center);
        $tg->addText('Thời gian đào tạo:   ', $bold);
        $tg->addText(trim((string) $form['thoi_gian_dt']) !== '' ? $form['thoi_gian_dt'].' tháng' : '');
        $right->addTextBreak(1, [], $p0);
        $kg = $right->addTextRun($p0 + ['alignment' => Jc::START, 'indentation' => ['left' => 400]]);
        $kg->addText('Ngày khai giảng:          ', $bold);
        $kg->addText(BaoCaoDeNghiSatHachData::ngay($form['ngay_kg']));
        $bg = $right->addTextRun($p0 + ['alignment' => Jc::START, 'indentation' => ['left' => 400]]);
        $bg->addText('Ngày mãn khóa:           ', $bold);
        $bg->addText(BaoCaoDeNghiSatHachData::ngay($form['ngay_bg']));

        $section->addTextBreak(1, ['size' => 4], $p0);
    }

    private function bangThiSinh($section, array $hocVien): void
    {
        $tbl = $section->addTable([
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMarginLeft' => 60,
            'cellMarginRight' => 60,
            'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
        ]);

        $p = ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::CENTER];
        $pLeft = ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::START];
        $pRight = ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::END];
        $h = ['bold' => true, 'size' => 9.5];
        $vc = ['valign' => VerticalJc::CENTER];
        $c = self::COT;

        $tbl->addRow(420, ['tblHeader' => true, 'cantSplit' => true]);
        $tbl->addCell($c[0], $vc + ['vMerge' => 'restart'])->addText('STT', $h, $p);
        $tbl->addCell($c[1], $vc + ['vMerge' => 'restart'])->addText('Họ và tên', $h, $p);
        $tbl->addCell($c[2], $vc + ['vMerge' => 'restart'])->addText('Ngày tháng năm sinh', $h, $p);
        $tbl->addCell($c[3], $vc + ['vMerge' => 'restart'])->addText('Số CMND', $h, $p);
        $tbl->addCell($c[4], $vc + ['vMerge' => 'restart'])->addText('Nơi đăng ký hộ khẩu', $h, $p);
        $tbl->addCell($c[5], $vc + ['vMerge' => 'restart'])->addText('Giấy CNSK hợp lệ', $h, $p);
        $tbl->addCell($c[6], $vc + ['vMerge' => 'restart'])->addText('Đã có GPLX hạng', $h, $p);
        $tbl->addCell($c[7] + $c[8], $vc + ['gridSpan' => 2])->addText('Thâm niên lái xe', $h, $p);
        $tbl->addCell($c[9], $vc + ['vMerge' => 'restart'])->addText('Số chứng chỉ nghề, giấy chứng nhận tốt nghiệp', $h, $p);
        $tbl->addCell($c[10], $vc + ['vMerge' => 'restart'])->addText('Ghi chú', $h, $p);

        $tbl->addRow(620, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ([0, 1, 2, 3, 4, 5, 6] as $i) {
            $tbl->addCell($c[$i], ['vMerge' => 'continue']);
        }
        $tbl->addCell($c[7], $vc)->addText('Số năm lái xe', $h, $p);
        $tbl->addCell($c[8], $vc)->addText('Số Km lái xe an toàn', $h, $p);
        $tbl->addCell($c[9], ['vMerge' => 'continue']);
        $tbl->addCell($c[10], ['vMerge' => 'continue']);

        $tbl->addRow(300, ['tblHeader' => true, 'cantSplit' => true]);
        foreach ($c as $i => $w) {
            $tbl->addCell($w, $vc)->addText('('.($i + 1).')', ['size' => 9.5], $p);
        }

        $f = ['size' => 10];
        foreach (array_values($hocVien) as $i => $hv) {
            $tbl->addRow(480, ['cantSplit' => true]);
            $tbl->addCell($c[0], $vc)->addText((string) ($i + 1), $f, $p);
            $tbl->addCell($c[1], $vc)->addText($hv['ho_ten'], $f, $pLeft);
            $tbl->addCell($c[2], $vc)->addText($hv['ngay_sinh'], $f, $pRight);
            $tbl->addCell($c[3], $vc)->addText($hv['so_cmnd'], $f, $p);
            $tbl->addCell($c[4], $vc)->addText($hv['noi_dk_ho_khau'], $f, $pLeft);
            $tbl->addCell($c[5], $vc)->addText($hv['giay_cnsk'] ? 'x' : '', $f, $p);
            $tbl->addCell($c[6], $vc)->addText($hv['da_co_gplx'], $f, $p);
            $tbl->addCell($c[7], $vc)->addText($hv['so_nam_lx'], $f, $p);
            $tbl->addCell($c[8], $vc)->addText($hv['so_km'], $f, $pRight);
            $tbl->addCell($c[9], $vc)->addText($hv['so_ccn'], $f, $pLeft);
            $tbl->addCell($c[10], $vc)->addText($hv['ghi_chu'], $f, $pLeft);
        }
    }

    private function chuKy($section, string $ngayBaoCao): void
    {
        $p = ['spaceAfter' => 0, 'spaceBefore' => 0, 'alignment' => Jc::CENTER, 'keepNext' => true];
        $ngay = Carbon::parse($ngayBaoCao);

        $section->addTextBreak(1, [], ['spaceAfter' => 0, 'keepNext' => true]);
        $tbl = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $tbl->addRow(null, ['cantSplit' => true]);
        $tbl->addCell(9500);
        $cell = $tbl->addCell(6000);
        $cell->addText('Ngày '.BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay), ['italic' => true, 'size' => 12], $p);
        $cell->addText('KT. GIÁM ĐỐC', ['bold' => true, 'size' => 12], $p);
        $cell->addText('PHÓ GIÁM ĐỐC', ['bold' => true, 'size' => 12], $p);
        $cell->addTextBreak(4, ['size' => 12], $p);
        $cell->addText(self::NGUOI_KY, ['bold' => true, 'size' => 12], $p);
    }
}
