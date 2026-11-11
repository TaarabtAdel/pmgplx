<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Quyết định thành lập hội đồng xét hoàn thành khóa đào tạo (+ danh sách kèm theo xuất Excel). */
final class QuyetDinhThanhLapHoiDongXetHoanThanhDocx
{
    public const GOC = 'templates/oto/quyet-dinh-thanh-lap-hoi-dong-xet-hoan-thanh-khoa.goc.docx';

    public const DOCX = 'templates/oto/quyet-dinh-thanh-lap-hoi-dong-xet-hoan-thanh-khoa.docx';

    public const GIAM_DOC_MAC_DINH = 'Nguyễn Thị Vinh';

    public const MO_TA_QUYET_DINH_MAU = 'Lớp đào tạo lái xe ô tô hạng B (Số sàn) Khóa 53, B (STĐ) khóa 35, B (STĐ) khóa 36 và các học viên vắng, thi lại các khóa trước';

    private const MO_TA_DIEU_1_MAU = 'hạng B (Số sàn) Khóa 53, B (STĐ) khóa 35, B (STĐ) khóa 36 và các học viên vắng, thi lại các khóa khác trước.';

    /** @var list<array{ho_ten: string, chuc_vu: string, chuc_danh: string}> */
    public const THANH_VIEN_MAC_DINH = [
        ['ho_ten' => 'Nguyễn Thị Vinh', 'chuc_vu' => 'Giám đốc', 'chuc_danh' => 'Chủ tịch hội đồng'],
        ['ho_ten' => 'Trương Hoàng Linh', 'chuc_vu' => 'Phó giám đốc Trung tâm', 'chuc_danh' => 'Phó chủ tịch hội đồng'],
        ['ho_ten' => 'Hồ Thị Tuyết Nhung', 'chuc_vu' => 'Nhân viên PĐT', 'chuc_danh' => 'Thư ký'],
        ['ho_ten' => 'Trần Linh Phương', 'chuc_vu' => 'Phó trưởng phòng - P. Thực hành', 'chuc_danh' => 'Ủy viên'],
        ['ho_ten' => 'Mai Thị Lệ Huyền', 'chuc_vu' => 'Giáo viên lý thuyết', 'chuc_danh' => 'Ủy viên'],
    ];

    public static function moTaMacDinhTuKhoa(string $hang, string $khoaSo): string
    {
        $hang = trim($hang);
        $khoaSo = trim($khoaSo);
        if ($hang === '' && $khoaSo === '') {
            return '';
        }

        return 'Lớp đào tạo lái xe ô tô hạng '.$hang.($khoaSo !== '' ? ' Khóa '.$khoaSo : '').' và các học viên vắng, thi lại các khóa trước';
    }

    public static function moTaDieu1MacDinhTuKhoa(string $hang, string $khoaSo): string
    {
        $hang = trim($hang);
        $khoaSo = trim($khoaSo);

        return 'hạng '.$hang.($khoaSo !== '' ? ' Khóa '.$khoaSo : '').' và các học viên vắng, thi lại các khóa khác trước.';
    }

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::GOC), resource_path(self::DOCX), [
            '516/QĐ-TTML' => '${SO_QD}',
            'ngày 18 tháng 08 năm 2026' => 'ngày ${NGAY_DONG}',
            self::MO_TA_QUYET_DINH_MAU => '${MO_TA_QUYET_DINH}',
            self::MO_TA_DIEU_1_MAU => '${MO_TA_DIEU_1}',
            'Nguyễn Thị Vinh' => '${GIAM_DOC}',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boBangRongCuoi($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
            self::canGiuaDongNgay($xpath);
        });
    }

    private static function canGiuaDongNgay(\DOMXPath $xpath): void
    {
        $p = $xpath->query('//w:p[contains(., "Quảng Trị") and contains(., "ngày")]')->item(0);
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
     * @param  array{so_qd: string, ngay_quyet_dinh: string, mo_ta_quyet_dinh: string, mo_ta_dieu_1: string, giam_doc: string}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-qd-thanh-lap-hoi-dong-xet-ht-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay),
            'MO_TA_QUYET_DINH' => $data['mo_ta_quyet_dinh'],
            'MO_TA_DIEU_1' => $data['mo_ta_dieu_1'],
            'GIAM_DOC' => $data['giam_doc'],
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc((string) $value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định.');
        }

        return $outputPath;
    }

    public static function dongKemTheo(string $soQd, Carbon $ngay): string
    {
        return '(Kèm theo quyết định số: '.$soQd.QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD
            .' ngày '.BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay)
            ."          \ncủa Trung tâm GDNN Mạnh Linh)";
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
