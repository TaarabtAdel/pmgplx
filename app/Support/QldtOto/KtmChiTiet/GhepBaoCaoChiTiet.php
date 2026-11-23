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
     * @param  array{lt?: bool, mp?: bool, cc?: bool, h?: bool, duong?: bool}  $coFile  true = đã tải file môn đó trong lần ghép này
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
            'duong' => false,
        ], $coFile);
        $canhBao = [];
        $out = [];

        foreach ($danhSach['thi_sinh'] as $row) {
            $sbd = $row['sbd'];
            $cccd = $row['cccd'];
            $phaiThi = GhiChuKtm::phaiThiChoSatHach($row['ghi_chu']);
            $cu = self::timKetQuaCu($ketQuaCu, $sbd, $cccd);

            $kqLt = self::ketQuaMon($phaiThi['lt'], self::traDiem($lt, $sbd, $cccd), 'VLT', $coFile['lt'], self::giaTriCu($cu, 'lt'));
            $kqMp = self::ketQuaMon($phaiThi['mp'], self::traDiem($mp, $sbd, $cccd), 'VMP', $coFile['mp'], self::giaTriCu($cu, 'mp'));
            $kqCc = self::ketQuaMon($phaiThi['cc'], self::traDiem($cc, $sbd, $cccd), 'VCC', $coFile['cc'], self::giaTriCu($cu, 'cc'));

            $coHDat = self::coTrongBang($hDat, $sbd, $cccd);
            $coHTruot = self::coTrongBang($hTruot, $sbd, $cccd);
            if ($coFile['h']) {
                $kqH = self::monHinh($phaiThi['h'], $coHDat ? 'Đ' : null, $coHTruot ? 'KĐ' : null, true);
            } else {
                $kqH = self::ketQuaMon($phaiThi['h'], null, 'VH', false, self::giaTriCu($cu, 'h'));
            }

            $datH = $kqH === 'Đ' || ($phaiThi['h'] && $coHDat);
            $coDtDat = self::coTrongBang($dtDat, $sbd, $cccd);
            $coDtTruot = self::coTrongBang($dtTruot, $sbd, $cccd);
            if ($coFile['duong']) {
                $kqDt = self::monDuong(
                    $phaiThi['duong'],
                    $datH || ! $phaiThi['h'],
                    $coDtDat ? 'Đ' : null,
                    $coDtTruot ? 'KĐ' : null,
                    true,
                );
                if ($phaiThi['duong'] && $phaiThi['h'] && $kqH === 'KĐ') {
                    $kqDt = '-';
                }
            } else {
                $kqDt = self::ketQuaMon($phaiThi['duong'], null, 'VĐ', false, self::giaTriCu($cu, 'duong'));
                if ($coFile['h'] && $phaiThi['duong'] && $phaiThi['h'] && $kqH === 'KĐ') {
                    $kqDt = '-';
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

        return ['thi_sinh' => $out, 'canh_bao' => $canhBao];
    }

    /** @param  array<string, string>  $bang */
    private static function coTrongBang(array $bang, string $sbd, string $cccd): bool
    {
        if ($sbd !== '' && array_key_exists($sbd, $bang)) {
            return true;
        }

        return $cccd !== '' && array_key_exists($cccd, $bang);
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
            return self::monKetQua(true, $tuFile, $maVang, true);
        }
        if ($cu !== null) {
            return $cu;
        }

        return '-';
    }

    /** @param  array<string, string>  $bang */
    private static function traDiem(array $bang, string $sbd, string $cccd): ?string
    {
        if ($sbd !== '' && isset($bang[$sbd])) {
            return $bang[$sbd];
        }
        if ($cccd !== '' && isset($bang[$cccd])) {
            return $bang[$cccd];
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

    private static function monHinh(bool $phaiThi, ?string $dat, ?string $truot, bool $coFile): string
    {
        if (! $phaiThi) {
            return '-';
        }
        if (! $coFile) {
            return '-';
        }
        if ($dat !== null) {
            return 'Đ';
        }
        if ($truot !== null) {
            return 'KĐ';
        }

        return 'VH';
    }

    private static function monDuong(bool $phaiThi, bool $duocPhepThi, ?string $dat, ?string $truot, bool $coFile): string
    {
        if (! $phaiThi) {
            return '-';
        }
        if (! $coFile) {
            return '-';
        }
        if (! $duocPhepThi) {
            return '-';
        }
        if ($dat !== null) {
            return 'Đ';
        }
        if ($truot !== null) {
            return 'KĐ';
        }

        return 'VĐ';
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