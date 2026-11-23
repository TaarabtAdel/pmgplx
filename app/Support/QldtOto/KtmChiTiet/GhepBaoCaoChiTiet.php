<?php

namespace App\Support\QldtOto\KtmChiTiet;

use App\Support\QldtOto\KtmBaoCao\GhiChuKtm;

final class GhepBaoCaoChiTiet
{
    /**
     * @param  array{
     *     ten_khoa: string,
     *     ngay_thi_tu_file: string,
     *     dia_diem: string,
     *     thi_sinh: list<array{stt: int, sbd: string, ho_ten: string, cccd: string, ngay_sinh: string, hang: string, ghi_chu: string}>
     * }  $danhSach
     * @param  array<string, string>  $lt  sbd => Đ|KĐ
     * @param  array<string, string>  $mp
     * @param  array<string, string>  $cc  cccd => Đ|KĐ
     * @param  array<string, string>  $hDat  sbd => Đ
     * @param  array<string, string>  $hTruot  sbd => KĐ
     * @param  array<string, string>  $dtDat
     * @param  array<string, string>  $dtTruot
     * @param  array{lt?: bool, mp?: bool, cc?: bool, h?: bool, h_dat?: bool, h_truot?: bool, duong?: bool, duong_dat?: bool, duong_truot?: bool}  $coFile
     * @param  array<string, array<string, mixed>>  $ketQuaCu  kết quả đã lưu (khóa CCCD), để cập nhật từng môn
     * @return array{thi_sinh: list<array<string, mixed>>, canh_bao: list<string>}
     */
    public static function ghep(
        array $danhSach,
        array $lt,
        array $mp,
        array $cc,
        array $hDat,
        array $hTruot,
        array $dtDat,
        array $dtTruot,
        array $coFile = [],
        array $ketQuaCu = [],
    ): array {
        $coFile = array_merge([
            'lt' => false,
            'mp' => false,
            'cc' => false,
            'h' => false,
            'h_dat' => false,
            'h_truot' => false,
            'duong' => false,
            'duong_dat' => false,
            'duong_truot' => false,
        ], $coFile);
        $canhBao = [];
        $out = [];
        $hTrongFileKhopDs = 0;
        $dtTrongFileKhopDs = 0;

        foreach ($danhSach['thi_sinh'] as $row) {
            $sbd = $row['sbd'];
            $cccd = $row['cccd'];
            $phaiThi = GhiChuKtm::phaiThiChoSatHach($row['ghi_chu']);
            $cu = self::timKetQuaCu($ketQuaCu, $sbd, $cccd);

            $tra = fn (array $bang) => self::traDiem($bang, $sbd, $cccd, $row['ho_ten'], $row['ngay_sinh']);

            $kqLt = self::ketQuaMon($phaiThi['lt'], $tra($lt), 'VLT', $coFile['lt'], self::giaTriCu($cu, 'lt'));
            $kqMp = self::ketQuaMon($phaiThi['mp'], $tra($mp), 'VMP', $coFile['mp'], self::giaTriCu($cu, 'mp'));
            $kqCc = self::ketQuaMon($phaiThi['cc'], $tra($cc), 'VCC', $coFile['cc'], self::giaTriCu($cu, 'cc'));

            $coHDat = self::coTrongBang($hDat, $sbd, $cccd, $row['ho_ten'], $row['ngay_sinh']);
            $coHTruot = self::coTrongBang($hTruot, $sbd, $cccd, $row['ho_ten'], $row['ngay_sinh']);
            $kqH = '-';
            if ($phaiThi['h']) {
                if ($coFile['h']) {
                    if ($coHDat || $coHTruot) {
                        $hTrongFileKhopDs++;
                    }
                    $kqH = self::monHinhTuDanhSach(
                        $coHDat,
                        $coHTruot,
                        (bool) $coFile['h_dat'],
                        (bool) $coFile['h_truot'],
                        $kqLt,
                        $cu,
                    );
                } else {
                    $kqH = self::ketQuaMon($phaiThi['h'], null, 'VH', false, self::giaTriCu($cu, 'h'));
                }
            }

            $coDtDat = self::coTrongBang($dtDat, $sbd, $cccd, $row['ho_ten'], $row['ngay_sinh']);
            $coDtTruot = self::coTrongBang($dtTruot, $sbd, $cccd, $row['ho_ten'], $row['ngay_sinh']);
            if ($phaiThi['h'] && $kqLt === 'KĐ' && $coFile['h']) {
                $kqH = '-';
            } elseif ($phaiThi['h'] && $kqLt === 'KĐ') {
                $cuH = self::giaTriCu($cu, 'h');
                if ($cuH === null || $cuH === 'VH') {
                    $kqH = '-';
                }
            }

            $kqDt = '-';
            if ($phaiThi['duong']) {
                if ($coFile['duong']) {
                    if ($coDtDat || $coDtTruot) {
                        $dtTrongFileKhopDs++;
                    }
                    $kqDt = self::monDuongTuDanhSach(
                        $coDtDat,
                        $coDtTruot,
                        (bool) $coFile['duong_dat'],
                        (bool) $coFile['duong_truot'],
                        $kqH,
                        $cu,
                    );
                } else {
                    $kqDt = self::ketQuaMon(true, null, 'VĐ', false, self::giaTriCu($cu, 'duong'));
                }
            }

            $tong = self::tongHop($phaiThi, $kqLt, $kqMp, $kqH, $kqCc, $kqDt);

            $out[] = [
                'stt' => $row['stt'],
                'sbd' => $sbd,
                'hoTen' => $row['ho_ten'],
                'soDinhDanh' => $cccd,
                'ngaySinh' => $row['ngay_sinh'],
                'hang' => $row['hang'],
                'ghiChu' => $row['ghi_chu'],
                'lt' => $kqLt,
                'mp' => $kqMp,
                'h' => $kqH,
                'cc' => $kqCc,
                'duong' => $kqDt,
                'tongHop' => $tong,
                'lanShLt' => self::lanSh($kqLt),
                'lanShMp' => self::lanSh($kqMp),
                'lanShH' => self::lanSh($kqH),
                'lanShCc' => self::lanSh($kqCc),
                'lanShDuong' => self::lanSh($kqDt),
            ];
        }

        if ($coFile['h']) {
            if ($hDat === [] && $hTruot === []) {
                $canhBao[] = 'File Hình không có dòng hợp lệ (thiếu tiêu đề CCCD / Họ tên / SBD).';
            } elseif ($hTrongFileKhopDs === 0) {
                $canhBao[] = 'File Hình không khớp thí sinh nào trong danh sách (kiểm CCCD hoặc Họ tên + Ngày sinh).';
            }
        }
        if ($coFile['duong']) {
            if ($dtDat === [] && $dtTruot === []) {
                $canhBao[] = 'File Đường trường không có dòng hợp lệ (thiếu tiêu đề CCCD / Họ tên / SBD).';
            } elseif ($dtTrongFileKhopDs === 0) {
                $canhBao[] = 'File Đường trường không khớp thí sinh nào trong danh sách (kiểm CCCD hoặc Họ tên + Ngày sinh).';
            }
        }

        return ['thi_sinh' => $out, 'canh_bao' => $canhBao];
    }

    /**
     * Tối đa 5 dòng sau ghép — ưu tiên thí sinh khớp file điểm/danh sách vừa tải (để nhân viên dò).
     *
     * @param  list<array<string, mixed>>  $thiSinh
     * @param  list<array<string, string>>  $bangDaTai  các bảng đọc từ file vừa upload (bỏ qua nếu rỗng)
     * @return list<array<string, mixed>>
     */
    public static function xemTruoc5Dong(array $thiSinh, array $bangDaTai): array
    {
        if ($thiSinh === []) {
            return [];
        }

        $bangDaTai = array_values(array_filter($bangDaTai, static fn (array $b): bool => $b !== []));
        if ($bangDaTai === []) {
            return array_slice(array_map(self::dongXemTruoc(...), $thiSinh), 0, 5);
        }

        $uuTien = [];
        $khac = [];
        foreach ($thiSinh as $ts) {
            $row = self::dongXemTruoc($ts);
            $trongFile = false;
            foreach ($bangDaTai as $bang) {
                if (self::thiSinhTrongBang($ts, $bang)) {
                    $trongFile = true;
                    break;
                }
            }
            if ($trongFile) {
                $uuTien[] = $row;
            } else {
                $khac[] = $row;
            }
        }

        return array_slice(array_merge($uuTien, $khac), 0, 5);
    }

    /**
     * @param  array<string, mixed>  $ts
     * @param  array<string, string>  $bang
     */
    public static function thiSinhTrongBang(array $ts, array $bang): bool
    {
        return self::coTrongBang(
            $bang,
            (string) ($ts['sbd'] ?? ''),
            (string) ($ts['soDinhDanh'] ?? ''),
            (string) ($ts['hoTen'] ?? ''),
            (string) ($ts['ngaySinh'] ?? ''),
        );
    }

    /** @param  array<string, mixed>  $ts */
    private static function dongXemTruoc(array $ts): array
    {
        return [
            'stt' => $ts['stt'] ?? '',
            'hoTen' => $ts['hoTen'] ?? '',
            'ngaySinh' => $ts['ngaySinh'] ?? '',
            'soDinhDanh' => $ts['soDinhDanh'] ?? '',
            'hang' => $ts['hang'] ?? '',
            'lt' => $ts['lt'] ?? '',
            'mp' => $ts['mp'] ?? '',
            'h' => $ts['h'] ?? '',
            'cc' => $ts['cc'] ?? '',
            'duong' => $ts['duong'] ?? '',
            'tongHop' => $ts['tongHop'] ?? '',
            'ghiChu' => $ts['ghiChu'] ?? '',
        ];
    }

    /**
     * Số thí sinh có trong file vừa upload (khớp CCCD / SBD / Họ tên + NS) — đếm sau ghép.
     *
     * @param  list<array<string, mixed>>  $thiSinhGhep
     * @param  array<string, string>  $bang
     */
    public static function demThiSinhKhopBangTuGhep(array $thiSinhGhep, array $bang): int
    {
        if ($bang === []) {
            return 0;
        }
        $n = 0;
        foreach ($thiSinhGhep as $ts) {
            if (self::coTrongBang(
                $bang,
                (string) ($ts['sbd'] ?? ''),
                (string) ($ts['soDinhDanh'] ?? ''),
                (string) ($ts['hoTen'] ?? ''),
                (string) ($ts['ngaySinh'] ?? ''),
            )) {
                $n++;
            }
        }

        return $n;
    }

    /** @param  array<string, string>  $bang */
    private static function coTrongBang(array $bang, string $sbd, string $cccd, string $hoTen = '', string $ngaySinh = ''): bool
    {
        $cccd = DocSheet::normalizeCccd($cccd);
        if ($cccd !== '' && array_key_exists($cccd, $bang)) {
            return true;
        }
        $kTen = DocSheet::khoaHoTenNgaySinh($hoTen, $ngaySinh);
        if ($kTen !== '' && array_key_exists($kTen, $bang)) {
            return true;
        }
        $sbd = DocSheet::normalizeSbd($sbd);

        return $sbd !== '' && array_key_exists($sbd, $bang);
    }

    /** @param  array<string, array<string, mixed>>  $ketQuaCu */
    private static function timKetQuaCu(array $ketQuaCu, string $sbd, string $cccd): ?array
    {
        $cccd = DocSheet::normalizeCccd($cccd);
        if ($cccd !== '' && isset($ketQuaCu[$cccd])) {
            return $ketQuaCu[$cccd];
        }
        $sbd = DocSheet::normalizeSbd($sbd);
        if ($sbd !== '' && isset($ketQuaCu['sbd:'.$sbd])) {
            return $ketQuaCu['sbd:'.$sbd];
        }

        return null;
    }

    /** @param  array<string, mixed>|null  $cu */
    private static function giaTriCu(?array $cu, string $field): ?string
    {
        if ($cu === null) {
            return null;
        }
        $v = trim((string) ($cu[$field] ?? ''));

        return $v !== '' && $v !== '-' ? $v : null;
    }

    private static function ketQuaMon(bool $phaiThi, ?string $tuFile, string $maVang, bool $coFileMoi, ?string $cu): string
    {
        if (! $phaiThi) {
            return '-';
        }
        if ($coFileMoi) {
            if ($tuFile !== null && $tuFile !== '') {
                return $tuFile;
            }
            if ($cu !== null) {
                return $cu;
            }

            return $maVang;
        }
        if ($cu !== null) {
            return $cu;
        }

        return '-';
    }

    /** @param  array<string, string>  $bang */
    private static function traDiem(array $bang, string $sbd, string $cccd, string $hoTen = '', string $ngaySinh = ''): ?string
    {
        $cccd = DocSheet::normalizeCccd($cccd);
        if ($cccd !== '' && isset($bang[$cccd])) {
            return $bang[$cccd];
        }
        $kTen = DocSheet::khoaHoTenNgaySinh($hoTen, $ngaySinh);
        if ($kTen !== '' && isset($bang[$kTen])) {
            return $bang[$kTen];
        }
        $sbd = DocSheet::normalizeSbd($sbd);
        if ($sbd !== '' && isset($bang[$sbd])) {
            return $bang[$sbd];
        }

        return null;
    }

    private static function monKetQua(bool $phaiThi, ?string $ketQua, string $maVang, bool $coFile): string
    {
        if (! $phaiThi) {
            return '-';
        }
        if (! $coFile) {
            return '-';
        }
        if ($ketQua === null || $ketQua === '') {
            return $maVang;
        }

        return $ketQua;
    }

    /**
     * File Hình: danh sách đạt / trượt — có trong file thì Đ/KĐ; không có thì giữ cũ (không gán VH).
     *
     * @param  array<string, mixed>|null  $cu
     */
    private static function monHinhTuDanhSach(
        bool $coHDat,
        bool $coHTruot,
        bool $coFileDat,
        bool $coFileTruot,
        string $kqLt,
        ?array $cu,
    ): string {
        if ($coHDat) {
            return 'Đ';
        }
        if ($coHTruot) {
            return 'KĐ';
        }
        if ($coFileDat && $coFileTruot) {
            return $kqLt === 'Đ' ? 'VH' : '-';
        }

        return self::giaTriHGiuNguyen($cu);
    }

    /** @param  array<string, mixed>|null  $cu */
    private static function giaTriHGiuNguyen(?array $cu): string
    {
        if ($cu === null) {
            return '-';
        }
        $v = trim((string) ($cu['h'] ?? ''));

        return $v !== '' ? $v : '-';
    }

    /**
     * File Đường: danh sách đạt / trượt — có trong file thì ghi Đ/KĐ; không có thì giữ giá trị cũ (không gán VĐ).
     *
     * @param  array<string, mixed>|null  $cu
     */
    private static function monDuongTuDanhSach(
        bool $coDtDat,
        bool $coDtTruot,
        bool $coFileDat,
        bool $coFileTruot,
        string $kqH,
        ?array $cu,
    ): string {
        if ($coDtDat) {
            return 'Đ';
        }
        if ($coDtTruot) {
            return 'KĐ';
        }
        if ($coFileDat && $coFileTruot) {
            return $kqH === 'Đ' ? 'VĐ' : '-';
        }

        return self::giaTriDuongGiuNguyen($cu);
    }

    /** @param  array<string, mixed>|null  $cu */
    private static function giaTriDuongGiuNguyen(?array $cu): string
    {
        if ($cu === null) {
            return '-';
        }
        $v = trim((string) ($cu['duong'] ?? ''));

        return $v !== '' ? $v : '-';
    }

    private static function lanSh(string $kq): int
    {
        return in_array($kq, ['Đ', 'KĐ'], true) ? 1 : 0;
    }

    /**
     * @param  array{lt: bool, mp: bool, h: bool, duong: bool, cc: bool}  $phaiThi
     */
    private static function tongHop(array $phaiThi, string $lt, string $mp, string $h, string $cc, string $dt): string
    {
        $map = ['lt' => $lt, 'mp' => $mp, 'h' => $h, 'cc' => $cc, 'duong' => $dt];
        $coMon = false;
        foreach ($map as $key => $kq) {
            if (! $phaiThi[$key]) {
                continue;
            }
            if ($kq === '-') {
                return '-';
            }
            $coMon = true;
            if ($kq !== 'Đ') {
                return 'KĐ';
            }
        }

        return $coMon ? 'Đ' : '-';
    }
}