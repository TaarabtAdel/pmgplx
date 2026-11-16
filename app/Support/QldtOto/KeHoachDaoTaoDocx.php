<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class KeHoachDaoTaoDocx
{
    /**
     * @param  array<string, Carbon|null>  $moc
     * @param  array<string, int|string|null>  $them
     */
    public static function generate(
        string $maKh,
        string $hang,
        array $moc,
        array $them,
        string $outPath
    ): void {
        $cfg = config('qldt_oto_ke_hoach_dao_tao.'.$hang);
        if (! is_array($cfg)) {
            throw new \RuntimeException('Chưa cấu hình mẫu kế hoạch cho hạng: '.$hang);
        }

        $template = resource_path('templates/oto/'.$cfg['template']);
        if (! is_file($template)) {
            throw new \RuntimeException('Không tìm thấy mẫu: '.$cfg['template']);
        }

        if (! copy($template, $outPath)) {
            throw new \RuntimeException('Không sao chép được mẫu Word.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($outPath) !== true) {
            throw new \RuntimeException('Không mở được file Word để ghi.');
        }

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML((string) $zip->getFromName('word/document.xml'));
        $xpath = DocxTextReplacer::xpath($dom);
        $body = $xpath->query('/w:document/w:body')->item(0);
        if (! $body instanceof \DOMElement) {
            throw new \RuntimeException('document.xml không hợp lệ.');
        }

        $kg = $moc['ngay_khai_giang'];
        if (! $kg instanceof Carbon) {
            throw new \RuntimeException('Thiếu ngày khai giảng.');
        }

        $ket = KeHoachDaoTaoLich::ngayKetKhoa($moc);
        $soCot = (int) ($cfg['so_cot_tuan'] ?? 9);
        $tuan = KeHoachDaoTaoLich::danhSachTuan($kg, $ket, $soCot);
        $kyHieu = KeHoachDaoTaoLich::kyHieuTheoTuan($moc, $tuan);

        self::capNhatPhanDau($xpath, $body, $them, $moc, $kg, $ket, $cfg);
        self::capNhatBangLich($xpath, $body, $tuan, $kyHieu, $soCot, $cfg);
        self::capNhatGhiChuVaCuoi($xpath, $body, $moc, $cfg, $them);

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();
    }

    /**
     * @param  array<string, int|string|null>  $them
     * @param  array<string, Carbon|null>  $moc
     * @param  array<string, mixed>  $cfg
     */
    private static function capNhatPhanDau(
        \DOMXPath $xpath,
        \DOMElement $body,
        array $them,
        array $moc,
        Carbon $kg,
        Carbon $ket,
        array $cfg
    ): void {
        $khoaSo = (string) ($them['khoa_so'] ?? '');
        $hangHt = (string) ($them['hang_hien_thi'] ?? '');
        $soHv = (int) ($them['so_hv'] ?? 0);
        $thoiGian = KeHoachDaoTaoLich::thoiGianThang($kg, $ket);
        $tu = KeHoachDaoTaoLich::dinhDangDdMmYyyy($kg);
        $den = KeHoachDaoTaoLich::dinhDangDdMmYyyy($ket);

        foreach ($xpath->query('.//w:p', $body) ?: [] as $p) {
            if (! $p instanceof \DOMElement) {
                continue;
            }
            $text = $p->textContent;
            if (str_contains($text, 'Khóa:')) {
                $moi = preg_replace('/Khóa:\s*\S+/u', 'Khóa: '.$khoaSo, $text) ?? $text;
                if ($hangHt !== '') {
                    $moi = preg_replace('/Hạng:\s*[^\r\n]+/u', 'Hạng: '.$hangHt, $moi) ?? $moi;
                }
                DocxTextReplacer::setParagraphText($xpath, $p, $moi);
            }
            if (str_contains($text, 'Thời gian đào tạo:')) {
                DocxTextReplacer::setParagraphText(
                    $xpath,
                    $p,
                    'Thời gian đào tạo: '.$thoiGian.' (từ ngày '.$tu.' đến ngày '.$den.')'
                );
            }
        }

        if ($soHv > 0) {
            foreach ($xpath->query('.//w:p', $body) ?: [] as $p) {
                if (! $p instanceof \DOMElement) {
                    continue;
                }
                if (! str_contains($p->textContent, 'Phân phối giờ học')) {
                    continue;
                }
                $moi = preg_replace('/\(\d+\s*học viên\)/u', '('.$soHv.' học viên)', $p->textContent) ?? $p->textContent;
                DocxTextReplacer::setParagraphText($xpath, $p, $moi);
            }
        }
    }

    /**
     * @param  list<array{tu: Carbon, den: Carbon}>  $tuan
     * @param  list<array{lt: string, hi: string, cb: string, th: string, kt: string, dp: string}>  $kyHieu
     * @param  array<string, mixed>  $cfg
     */
    private static function capNhatBangLich(
        \DOMXPath $xpath,
        \DOMElement $body,
        array $tuan,
        array $kyHieu,
        int $soCot,
        array $cfg
    ): void {
        $tbl = self::bangLich($xpath, $body);
        if ($tbl === null) {
            throw new \RuntimeException('Không tìm thấy bảng «Lịch học toàn khóa».');
        }

        $rows = iterator_to_array($xpath->query('w:tr', $tbl) ?: []);
        if (count($rows) < 11) {
            throw new \RuntimeException('Bảng lịch không đúng cấu trúc mẫu.');
        }

        $hangPhase = ['lt', 'hi', 'cb', 'th', 'kt', 'dp'];
        $rowPhaseIdx = [5, 6, 7, 8, 9, 10];

        self::xoaVeTrongBang($xpath, $tbl, $rowPhaseIdx, $soCot);

        self::dienHangTuan($xpath, $rows[1], $tuan, $soCot, false);
        self::dienHangTuan($xpath, $rows[2], $tuan, $soCot, true);
        self::dienHangTuan($xpath, $rows[4], $tuan, $soCot, false, true);

        foreach ($rowPhaseIdx as $i => $rowIdx) {
            $key = $hangPhase[$i];
            self::dienHangKyHieu($xpath, $rows[$rowIdx], $kyHieu, $soCot, $key);
        }

        $chuoiThang = KeHoachDaoTaoLich::chuoiThangTrenLuoi($tuan);
        if ($chuoiThang !== '' && isset($rows[0])) {
            $cells0 = iterator_to_array($xpath->query('w:tc', $rows[0]) ?: []);
            if (isset($cells0[2])) {
                DocxTextReplacer::setCellText($xpath, $cells0[2], $chuoiThang);
            }
        }
    }

    private static function bangLich(\DOMXPath $xpath, \DOMElement $body): ?\DOMElement
    {
        foreach ($xpath->query('.//w:tbl', $body) ?: [] as $tbl) {
            if (! $tbl instanceof \DOMElement) {
                continue;
            }
            if (str_contains($tbl->textContent, 'Tuần') && str_contains($tbl->textContent, 'Từ ngày')) {
                return $tbl;
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $rowPhaseIdx
     */
    private static function xoaVeTrongBang(\DOMXPath $xpath, \DOMElement $tbl, array $rowPhaseIdx, int $soCot): void
    {
        foreach ($xpath->query('.//w:drawing|.//w:pict', $tbl) ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }
        $rows = iterator_to_array($xpath->query('w:tr', $tbl) ?: []);
        foreach ([1, 2, 4] as $ri) {
            if (! isset($rows[$ri])) {
                continue;
            }
            $cells = iterator_to_array($xpath->query('w:tc', $rows[$ri]) ?: []);
            $start = 2;
            for ($i = 0; $i < max($soCot, count($cells) - $start); $i++) {
                $col = $start + $i;
                if (isset($cells[$col])) {
                    DocxTextReplacer::setCellText($xpath, $cells[$col], '');
                }
            }
        }
        foreach ($rowPhaseIdx as $ri) {
            if (! isset($rows[$ri])) {
                continue;
            }
            $cells = iterator_to_array($xpath->query('w:tc', $rows[$ri]) ?: []);
            for ($i = 0; $i < $soCot; $i++) {
                $col = 1 + $i;
                if (isset($cells[$col])) {
                    DocxTextReplacer::setCellText($xpath, $cells[$col], '');
                }
            }
        }
    }

    /**
     * @param  list<array{tu: Carbon, den: Carbon}>  $tuan
     */
    private static function dienHangTuan(
        \DOMXPath $xpath,
        \DOMElement $row,
        array $tuan,
        int $soCot,
        bool $hienTu,
        bool $hienDen = false
    ): void {
        $cells = iterator_to_array($xpath->query('w:tc', $row) ?: []);
        $start = 2;
        for ($i = 0; $i < $soCot; $i++) {
            $col = $start + $i;
            if (! isset($cells[$col])) {
                break;
            }
            if (! isset($tuan[$i])) {
                DocxTextReplacer::setCellText($xpath, $cells[$col], '');

                continue;
            }
            if ($hienDen) {
                $val = KeHoachDaoTaoLich::dinhDangDdMm($tuan[$i]['den']);
            } elseif ($hienTu) {
                $val = KeHoachDaoTaoLich::dinhDangDdMm($tuan[$i]['tu']);
            } else {
                $val = (string) ($i + 1);
            }
            DocxTextReplacer::setCellText($xpath, $cells[$col], $val);
        }
    }

    /**
     * @param  list<array{lt: string, hi: string, cb: string, th: string, kt: string, dp: string}>  $kyHieu
     */
    private static function dienHangKyHieu(
        \DOMXPath $xpath,
        \DOMElement $row,
        array $kyHieu,
        int $soCot,
        string $key
    ): void {
        $cells = iterator_to_array($xpath->query('w:tc', $row) ?: []);
        $start = 1;
        for ($i = 0; $i < $soCot; $i++) {
            $col = $start + $i;
            if (! isset($cells[$col])) {
                break;
            }
            $val = $kyHieu[$i][$key] ?? '';
            DocxTextReplacer::setCellText($xpath, $cells[$col], $val);
        }
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     * @param  array<string, mixed>  $cfg
     * @param  array<string, int|string|null>  $them
     */
    private static function capNhatGhiChuVaCuoi(
        \DOMXPath $xpath,
        \DOMElement $body,
        array $moc,
        array $cfg,
        array $them
    ): void {
        $tuLt = $moc[$cfg['ngay_lt_tu'] ?? 'ngay_khai_giang'] ?? null;
        $denLt = $moc[$cfg['ngay_lt_den'] ?? 'ngay_bd_thuc_hanh'] ?? null;
        if ($denLt instanceof Carbon) {
            $denLt = $denLt->copy()->subDay();
        }
        if ($denLt === null && $tuLt instanceof Carbon) {
            $denLt = $tuLt->copy()->addDays(17);
        }

        $tuDp = $moc[$cfg['ngay_dp_tu'] ?? 'ngay_bo_sung_dat'] ?? null;
        $denDp = $moc[$cfg['ngay_dp_den'] ?? 'ngay_kt_bo_sung_dat'] ?? $tuDp;

        $ghiChu = sprintf(
            (string) ($cfg['ghi_chu_lt'] ?? ''),
            KeHoachDaoTaoLich::dinhDangDdMmYyyy($tuLt),
            KeHoachDaoTaoLich::dinhDangDdMmYyyy($denLt),
            KeHoachDaoTaoLich::dinhDangDdMmYyyy($tuDp),
            KeHoachDaoTaoLich::dinhDangDdMmYyyy($denDp)
        );

        foreach ($xpath->query('.//w:p', $body) ?: [] as $p) {
            if (! $p instanceof \DOMElement) {
                continue;
            }
            if (! str_starts_with(trim($p->textContent), 'GHI CHÚ:')) {
                continue;
            }
            DocxTextReplacer::setParagraphText($xpath, $p, 'GHI CHÚ: '.$ghiChu);
        }

        $gvK = (int) ($them['so_gv_khoa'] ?? 0);
        $gvD = (int) ($them['so_gv_dang_sd'] ?? $gvK);
        $gvT = (int) ($them['so_gv_tong'] ?? 0);
        $xeK = (int) ($them['so_xe_khoa'] ?? 0);
        $xeD = (int) ($them['so_xe_dang_sd'] ?? $xeK);
        $xeT = (int) ($them['so_xe_tong'] ?? 0);

        foreach ($xpath->query('.//w:p', $body) ?: [] as $p) {
            if (! $p instanceof \DOMElement) {
                continue;
            }
            $t = $p->textContent;
            if (str_contains($t, 'giáo viên khóa đào tạo')) {
                $moi = $gvK > 0
                    ? sprintf('1. Số giáo viên khóa đào tạo/Số Giáo viên đang sử dụng/Tổng số giáo viên hiện có: %d/%d/%d).',
                        $gvK, $gvD, $gvT > 0 ? $gvT : $gvD)
                    : $t;
                DocxTextReplacer::setParagraphText($xpath, $p, $moi);
            }
            if (str_contains($t, 'xe tập lái khóa đào tạo')) {
                $moi = $xeK > 0
                    ? sprintf('2. Số xe tập lái khóa đào tạo/ Số Xe tập lái đang sử dụng/ Tổng số xe tập lái hiện có: %d/%d/%d).',
                        $xeK, $xeD, $xeT > 0 ? $xeT : $xeD)
                    : preg_replace('/\(VD:[^)]+\)/', '', $t) ?? $t;
                DocxTextReplacer::setParagraphText($xpath, $p, trim($moi));
            }
        }
    }

    public static function demGiaoVienKhoa(string $maKh): int
    {
        return (int) KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('TrangThai', 1)
            ->select('MaGV')
            ->distinct()
            ->count();
    }

    public static function demXeTapKhoa(string $maKh): int
    {
        return (int) KhoaHocXeTap::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocXeTap', 0)
            ->where('TrangThai', 1)
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->distinct()
            ->count('BienSoXe');
    }

    /** @return array{gv: int, xe: int} */
    public static function demGvXeDangSuDung(): array
    {
        $gv = (int) DB::connection('sqlsrv')
            ->table('KhoaHoc_GiaoVien')
            ->where('TrangThai', 1)
            ->distinct()
            ->count('MaGV');
        $xe = (int) KhoaHocXeTap::query()
            ->where('IsKhoaHocXeTap', 0)
            ->where('TrangThai', 1)
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->distinct()
            ->count('BienSoXe');

        return ['gv' => $gv, 'xe' => $xe];
    }
}