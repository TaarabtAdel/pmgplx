<?php

namespace App\Support\QldtOto\KtmChiTiet;

use App\Models\QldtOto\KtmBaoCaoChiTiet;
use App\Support\QldtOto\KtmBaoCao\GhiChuKtm;
use App\Support\QldtOto\KtmBaoCao\KtmKyThiDuLieu;
use App\Support\QldtOto\KtmBaoCao\KtmThiSinh;
use App\Support\QldtOto\KtmBaoCao\KtmTinhToan;
use App\Support\QldtOto\KtmBaoCao\KtmTongHop;

/** Gộp nhiều báo cáo chi tiết đã lưu → dữ liệu tổng hợp (BBTH / BC CHUNG / Biên bản họp). */
final class KtmChiTietGopDuLieu
{
    public const HANG_B01 = 'b01';

    public const HANG_B = 'b';

    public const HANG_C1 = 'c1';

    public const HANG_C1_TCN = 'c1_tcn';

    public const HANG_QUA_HAN = 'qua_han';

    public const HANG_B01_THI_LAI = 'b01_thi_lai';

    /**
     * @param  list<int>  $ids
     */
    public static function tuBanGhiIds(array $ids): KtmKyThiDuLieu
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            throw new \InvalidArgumentException('Chưa chọn báo cáo chi tiết nào.');
        }

        $records = KtmBaoCaoChiTiet::query()->whereIn('id', $ids)->orderBy('NgayKyThi')->orderBy('id')->get();
        if ($records->isEmpty()) {
            throw new \InvalidArgumentException('Không tìm thấy báo cáo chi tiết.');
        }

        /** @var array<string, list<KtmThiSinh>> $buckets */
        $buckets = [
            self::HANG_B01 => [],
            self::HANG_B => [],
            self::HANG_C1 => [],
            self::HANG_C1_TCN => [],
            self::HANG_QUA_HAN => [],
            self::HANG_B01_THI_LAI => [],
        ];

        $tenKhoa = [];
        $ngayThi = '';
        $canhBao = [];
        $daCccd = [];

        foreach ($records as $record) {
            $tenKhoa[] = $record->TenKhoaThi;
            $ngayThi = $record->NgayKyThi->format('d/m/Y');
            foreach ($record->thiSinh() as $ts) {
                $cccd = preg_replace('/\D/', '', (string) ($ts['soDinhDanh'] ?? '')) ?? '';
                if ($cccd !== '' && isset($daCccd[$cccd])) {
                    $canhBao[] = 'CCCD trùng «'.$cccd.'» (bỏ qua bản ghi sau): '.$record->TenKhoaThi;

                    continue;
                }
                if ($cccd !== '') {
                    $daCccd[$cccd] = true;
                }
                $kt = self::thiSinhTuChiTiet($ts);
                $key = self::xepHang($kt->hang, (bool) $record->LaThiLai);
                $buckets[$key][] = $kt;
            }
        }

        $tongHopB01 = KtmTinhToan::tongHopNhom('B.01', $buckets[self::HANG_B01]);
        $tongHopB01Lai = KtmTinhToan::tongHopNhom('B.01 Thi Lại', $buckets[self::HANG_B01_THI_LAI]);
        $tongHopB = KtmTinhToan::tongHopNhom('B', $buckets[self::HANG_B]);
        $tongHopC1 = KtmTinhToan::tongHopNhom('C1', $buckets[self::HANG_C1]);
        $tongHopC1Tcn = KtmTinhToan::tongHopNhom('C1 TCN (THI LẠI)', $buckets[self::HANG_C1_TCN]);
        $tongHopQh = KtmTinhToan::tongHopNhom('QUÁ HẠN GPLX HẠNG B & C1', $buckets[self::HANG_QUA_HAN]);

        $cong = KtmTinhToan::tongCong(
            KtmTinhToan::tongCong($tongHopB01, $tongHopB01Lai),
            KtmTinhToan::tongCong(
                KtmTinhToan::tongCong($tongHopB, $tongHopC1),
                KtmTinhToan::tongCong($tongHopC1Tcn, $tongHopQh),
            ),
        );

        $thiSinh = [];
        foreach ($buckets as $list) {
            foreach ($list as $ts) {
                $thiSinh[] = $ts;
            }
        }
        usort($thiSinh, fn (KtmThiSinh $a, KtmThiSinh $b) => $a->stt <=> $b->stt);

        return new KtmKyThiDuLieu(
            tenKhoa: implode(' và ', array_unique($tenKhoa)),
            ngayThi: $ngayThi,
            tongHoSo: $cong->hoSo,
            g7TuFile: $cong->hoSo,
            thiSinh: $thiSinh,
            tongHopBTTml: $tongHopB01,
            tongHopKhac: $tongHopB01Lai,
            canhBao: $canhBao,
            tongHopTheoHang: [
                'b01' => $tongHopB01,
                'b01_thi_lai' => $tongHopB01Lai,
                'b' => $tongHopB,
                'c1' => $tongHopC1,
                'c1_tcn' => $tongHopC1Tcn,
                'qua_han' => $tongHopQh,
                'cong' => $cong,
            ],
        );
    }

    /** @param  array<string, mixed>  $ts */
    public static function thiSinhTuChiTiet(array $ts): KtmThiSinh
    {
        $ghiChu = (string) ($ts['ghiChu'] ?? '');
        $phaiThi = GhiChuKtm::phaiThiChoSatHach($ghiChu);
        $coMat = ! KtmChiTietLocDanhSach::laVang($ts);

        $lt = (string) ($ts['lt'] ?? '');
        $mp = (string) ($ts['mp'] ?? '');
        $h = (string) ($ts['h'] ?? '');
        $cc = (string) ($ts['cc'] ?? '');
        $duong = (string) ($ts['duong'] ?? '');
        $tongHop = (string) ($ts['tongHop'] ?? '');

        if ($tongHop === '' && $coMat) {
            $tongHop = 'KĐ';
        }
        if (! $coMat && $tongHop === '') {
            $tongHop = 'KĐ';
        }

        return new KtmThiSinh(
            stt: (int) ($ts['stt'] ?? 0),
            sbd: (string) ($ts['sbd'] ?? ''),
            hoTen: (string) ($ts['hoTen'] ?? ''),
            soDinhDanh: (string) ($ts['soDinhDanh'] ?? ''),
            ngaySinh: (string) ($ts['ngaySinh'] ?? ''),
            hang: (string) ($ts['hang'] ?? ''),
            ghiChu: $ghiChu,
            phaiThi: $phaiThi,
            coMat: $coMat,
            lt: $lt,
            mp: $mp,
            h: $h,
            cc: $cc,
            duong: $duong,
            tongHop: $tongHop,
            lanShLt: (int) ($ts['lanShLt'] ?? self::lanSh($lt)),
            lanShMp: (int) ($ts['lanShMp'] ?? self::lanSh($mp)),
            lanShH: (int) ($ts['lanShH'] ?? self::lanSh($h)),
            lanShCc: (int) ($ts['lanShCc'] ?? self::lanSh($cc)),
            lanShDuong: (int) ($ts['lanShDuong'] ?? self::lanSh($duong)),
        );
    }

    public static function xepHang(string $hang, bool $kyThiLai): string
    {
        $h = mb_strtoupper(trim($hang));
        if (str_contains($h, 'QUÁ HẠN') || str_contains($h, 'QUA HAN')) {
            return self::HANG_QUA_HAN;
        }
        if ($kyThiLai && (str_contains($h, 'C1') || str_contains($h, 'C.1'))) {
            return self::HANG_C1_TCN;
        }
        if ($kyThiLai && (str_contains($h, 'B.01') || str_contains($h, 'B01') || $h === 'B' || str_starts_with($h, 'B '))) {
            return self::HANG_B01_THI_LAI;
        }
        if (str_contains($h, 'B.01') || str_contains($h, 'B01')) {
            return self::HANG_B01;
        }
        if (preg_match('/^B(\s|$|\.)/u', $h)) {
            return self::HANG_B;
        }
        if (str_contains($h, 'C1') || str_contains($h, 'C.1')) {
            return self::HANG_C1;
        }

        return self::HANG_B01;
    }

    private static function lanSh(string $kq): int
    {
        return in_array($kq, ['Đ', 'KĐ'], true) ? 1 : 0;
    }

    /**
     * @param  array<string, KtmTongHop>|null  $meta
     */
    public static function tongHopHang(?array $meta, string $key): KtmTongHop
    {
        if (is_array($meta) && isset($meta[$key])) {
            return $meta[$key];
        }

        return KtmTinhToan::tongHopNhomRong('');
    }
}