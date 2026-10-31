<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Thông báo tổ chức kiểm tra kết thúc môn học — mẫu thong-bao-kiem-tra-ket-thuc-mon.goc.docx.
 * Khối ${KG_THI_LAI} (dòng «Kính gửi … thi lại») và ${THI_LAI} (dòng giới thiệu + bảng) bỏ khi không có học viên thi lại.
 */
final class ThongBaoKiemTraKetThucMonDocx
{
    public const GOC = 'templates/oto/thong-bao-kiem-tra-ket-thuc-mon.goc.docx';

    public const DOCX = 'templates/oto/thong-bao-kiem-tra-ket-thuc-mon.docx';

    public const HAU_TO_SO = '/QĐ-TTML';

    public const DIA_DIEM_MAC_DINH = 'Trung tâm sát hạch lái xe thuộc công ty TNHH xây dựng Mạnh Linh; địa chỉ Km6+800 Điện Biên Phủ, P. Đông Hà, tỉnh Quảng Trị';

    public const LY_DO_TRU_MAC_DINH = 'không tham gia đủ quãng đường, thời gian theo quy định';

    /** @var list<string> */
    public const BUOI = ['buổi sáng', 'buổi chiều', 'buổi tối'];

    /** @var list<string> */
    public const NOI_DUNG_KIEM_TRA = ['LT', 'MP', 'H', 'Đ', 'LT+MP', 'H+Đ', 'LT+MP+H+Đ'];

    private const THU = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

    private const COT_BANG = ['hv_stt', 'hv_ho_ten', 'hv_dinh_danh', 'hv_ngay_sinh', 'hv_hang', 'hv_noi_dung'];

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::GOC), resource_path(self::DOCX), [
            '488/QĐ-TTML' => '${SO_TB}',
            'Quảng Trị, ngày10 tháng 08 năm 2026' => 'Quảng Trị, ngày ${NGAY_DONG}',
            'ô tô hạng B (Số sàn) khóa 53' => 'ô tô hạng ${HANG} khóa ${KHOA_SO}',
            'hạng B (Số sàn) Khóa 53' => 'hạng ${HANG} Khóa ${KHOA_SO}',
            'buổi sáng lúc 7h ngày 16/08/2026 (Chủ Nhật)' => '${THOI_GIAN_1}',
            ' và kiểm tra lần 2 cho các học viên vắng, không đạt lần 1 vào buổi sáng 7h ngày 18/8/2026 (Thứ ba)' => '${LAN_2}',
            self::DIA_DIEM_MAC_DINH => '${DIA_DIEM}',
            'khóa BK53( Trừ học viên Nguyễn Công Sinh không tham gia đủ quãng đường, thời gian theo quy định)' => 'khóa ${TEN_LOP}${GHI_CHU_TRU}',
        ], function (\DOMXPath $xpath): void {
            self::ganBangThiLai($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    private static function ganBangThiLai(\DOMXPath $xpath): void
    {
        $tbl = $xpath->query('/w:document/w:body/w:tbl[.//w:t[contains(., "Số định danh")]]')->item(0);
        if (! $tbl instanceof \DOMElement) {
            throw new \RuntimeException('Không tìm thấy bảng học viên thi lại (cột «Số định danh»).');
        }
        $rows = iterator_to_array($xpath->query('w:tr', $tbl));
        if (count($rows) < 2) {
            throw new \RuntimeException('Bảng học viên thi lại thiếu dòng dữ liệu mẫu.');
        }
        $cells = iterator_to_array($xpath->query('w:tc', $rows[1]));
        foreach (self::COT_BANG as $i => $bien) {
            DocxTextReplacer::setCellText($xpath, $cells[$i], '${'.$bien.'}');
        }
        foreach (array_slice($rows, 2) as $tr) {
            $tbl->removeChild($tr);
        }

        $gioiThieu = $xpath->query('/w:document/w:body/w:p[contains(., "thi lại các khóa trước gồm")]')->item(0);
        $kinhGui = $xpath->query('/w:document/w:body/w:p[contains(., "thi lại các khóa trước.")]')->item(0);
        if (! $gioiThieu instanceof \DOMElement || ! $kinhGui instanceof \DOMElement) {
            throw new \RuntimeException('Không tìm thấy dòng «thi lại các khóa trước» trong mẫu.');
        }
        DocMauBuilder::bocKhoi($gioiThieu, $tbl, 'THI_LAI');
        DocMauBuilder::bocKhoi($kinhGui, $kinhGui, 'KG_THI_LAI');
    }

    /** «buổi sáng lúc 7h ngày 16/08/2026 (Chủ nhật)». */
    public static function thoiGian(string $buoi, string $gio, string $ngay): string
    {
        $d = Carbon::parse($ngay);

        return $buoi.' lúc '.$gio.' ngày '.$d->format('d/m/Y').' ('.self::THU[$d->dayOfWeek].')';
    }

    /**
     * @param  array{
     *     so_tb: string, ngay_thong_bao: string, hang: string, khoa_so: string, ten_lop: string,
     *     buoi_1: string, gio_1: string, ngay_1: string,
     *     co_lan_2?: bool, buoi_2?: string, gio_2?: string, ngay_2?: string,
     *     dia_diem: string, tru_ho_ten: list<string>, ly_do_tru: string,
     *     thi_lai: list<array{ho_ten: string, dinh_danh: string, ngay_sinh: string, hang: string, noi_dung: string}>,
     * }  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-thong-bao-kiem-tra-ket-thuc-mon-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $lan2 = ! empty($data['co_lan_2'])
            ? ' và kiểm tra lần 2 cho các học viên vắng, không đạt lần 1 vào '
                .self::thoiGian((string) $data['buoi_2'], (string) $data['gio_2'], (string) $data['ngay_2'])
            : '';
        $tru = $data['tru_ho_ten'] !== []
            ? ' (Trừ học viên '.implode(', ', $data['tru_ho_ten']).' '.$data['ly_do_tru'].')'
            : '';

        $processor = new TemplateProcessor($template);

        $thiLai = array_values($data['thi_lai']);
        $coThiLai = $thiLai !== [] ? 1 : 0;
        $processor->cloneBlock('KG_THI_LAI', $coThiLai);
        $processor->cloneBlock('THI_LAI', $coThiLai);
        if ($coThiLai) {
            $processor->cloneRow('hv_stt', count($thiLai));
            foreach ($thiLai as $i => $hv) {
                $n = $i + 1;
                $processor->setValue('hv_stt#'.$n, (string) $n);
                foreach (['ho_ten', 'dinh_danh', 'ngay_sinh', 'hang', 'noi_dung'] as $f) {
                    $processor->setValue('hv_'.$f.'#'.$n, self::esc($hv[$f]));
                }
            }
        }

        $values = [
            'SO_TB' => $data['so_tb'].self::HAU_TO_SO,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_thong_bao'])),
            'HANG' => $data['hang'],
            'KHOA_SO' => $data['khoa_so'],
            'THOI_GIAN_1' => self::thoiGian($data['buoi_1'], $data['gio_1'], $data['ngay_1']),
            'LAN_2' => $lan2,
            'DIA_DIEM' => $data['dia_diem'],
            'TEN_LOP' => $data['ten_lop'],
            'GHI_CHU_TRU' => $tru,
        ];
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file thông báo.');
        }

        return $outputPath;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
