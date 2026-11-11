<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/** Quyết định tổ chức kiểm tra kết thúc môn học lý thuyết, thực hành. */
final class QuyetDinhToChucKiemTraKetThucMonDocx
{
    public const GOC = 'templates/oto/quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon.goc.docx';

    public const DOCX = 'templates/oto/quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon.docx';

    public const DIA_DIEM_MAC_DINH = 'Trung tâm sát hạch lái xe thuộc công ty TNHH xây dựng Mạnh Linh, Km 6+800, Đường Điện Biên Phủ, Phường Đông Hà, tỉnh Quảng Trị';

    public const GHI_THI_LAI_MAC_DINH = ' và các học viên vắng thi lại các khóa trước đủ điều kiện dự kiểm tra (có danh sách kèm theo)';

    public const GIAM_DOC_MAC_DINH = 'Nguyễn Thị Vinh';

    private const KY_KIEM_TRA_MAU = 'được tiến hành lúc 07h ngày 16/08/2026 và kiểm tra lại cho các học viên không đạt vào lúc 07h ngày 18/8/2026 tại Trung tâm sát hạch lái xe thuộc công ty TNHH xây dựng Mạnh Linh, Km 6+800, Đường Điện Biên Phủ, Phường Đông Hà, tỉnh Quảng Trị.';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::GOC), resource_path(self::DOCX), [
            '506/QĐ-TTML' => '${SO_QD}',
            'Ngày 14 tháng 08 năm 2026' => 'Ngày ${NGAY_DONG}',
            'thực hành lái xe ô tô hạng B (Số sàn) khóa 53' => 'thực hành lái xe ô tô hạng ${HANG} khóa ${KHOA_SO}',
            '99 học viên hạng B (Số sàn) Khóa 53' => '${SO_HV} học viên hạng ${HANG} Khóa ${KHOA_SO}',
            ' và các học viên vắng thi lại các khóa trước đủ điều kiện dự kiểm tra (có danh sách kèm theo)' => '${GHI_THI_LAI}',
            self::KY_KIEM_TRA_MAU => '${KY_KIEM_TRA}',
            'Nguyễn Thị Vinh' => '${GIAM_DOC}',
        ], function (\DOMXPath $xpath): void {
            DocMauBuilder::boBangRongCuoi($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
            self::canGiuaDongNgay($xpath);
        });
    }

    private static function canGiuaDongNgay(\DOMXPath $xpath): void
    {
        $p = $xpath->query('//w:p[contains(., "Quảng Trị") and contains(., "Ngày")]')->item(0);
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

    public static function kyKiemTra(string $gio1, string $ngay1, bool $coLan2, string $gio2, string $ngay2, string $diaDiem): string
    {
        $d1 = Carbon::parse($ngay1)->format('d/m/Y');
        $lan1 = 'lúc '.trim($gio1).' ngày '.$d1;
        $lan2 = '';
        if ($coLan2) {
            $d2 = Carbon::parse($ngay2)->format('d/m/Y');
            $lan2 = ' và kiểm tra lại cho các học viên không đạt vào lúc '.trim($gio2).' ngày '.$d2;
        }

        return 'được tiến hành '.$lan1.$lan2.' tại '.trim($diaDiem).'.';
    }

    /**
     * @param  array{
     *     so_qd: string,
     *     ngay_quyet_dinh: string,
     *     so_hv: int|string,
     *     hang: string,
     *     khoa_so: string,
     *     ghi_thi_lai: string,
     *     gio_1: string,
     *     ngay_1: string,
     *     co_lan_2: bool,
     *     gio_2?: string,
     *     ngay_2?: string,
     *     dia_diem: string,
     *     giam_doc: string,
     * }  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-to-chuc-ktm-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay),
            'SO_HV' => (string) $data['so_hv'],
            'HANG' => $data['hang'],
            'KHOA_SO' => $data['khoa_so'],
            'GHI_THI_LAI' => $data['ghi_thi_lai'],
            'KY_KIEM_TRA' => self::kyKiemTra(
                $data['gio_1'],
                $data['ngay_1'],
                ! empty($data['co_lan_2']),
                (string) ($data['gio_2'] ?? ''),
                (string) ($data['ngay_2'] ?? ''),
                $data['dia_diem']
            ),
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

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
