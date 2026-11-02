<?php

namespace App\Support\OCR;

use App\Models\DaoTao\HocVienDoThongTin;
use App\Models\PMGPLX\NguoiLX;
use App\Support\PMGPLX\DmDvhcTenDayDu;
use App\Support\PMGPLX\NgayVn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Dò thông tin học viên từ ảnh CCCD / GPLX (4 mặt) — dùng cho modal «Dò thông tin» và trang chụp trên điện thoại.
 * Phiên dò (token) lưu trong cache để điện thoại gửi kết quả về máy tính đang mở modal.
 */
final class DoThongTinGiayTo
{
    public const MAT = [
        'cccd_truoc' => ['nhan' => 'CCCD mặt trước', 'loai' => 'cccd'],
        'cccd_sau' => ['nhan' => 'CCCD mặt sau', 'loai' => 'cccd'],
        'gplx_truoc' => ['nhan' => 'GPLX mặt trước', 'loai' => 'gplx'],
        'gplx_sau' => ['nhan' => 'GPLX mặt sau', 'loai' => 'gplx'],
    ];

    /** Trường kết quả => [nhãn, mặt giấy tờ, trường của GiayToParser]. */
    public const TRUONG = [
        'ho_ten' => ['Tên', 'cccd_truoc', 'ho_ten'],
        'so_cccd' => ['Số căn cước', 'cccd_truoc', 'so'],
        'ngay_sinh' => ['Ngày sinh', 'cccd_truoc', 'ngay_sinh'],
        'noi_thuong_tru' => ['Nơi thường trú', 'cccd_truoc', 'noi_thuong_tru'],
        'ngay_cap_cccd' => ['Ngày cấp CCCD', 'cccd_sau', 'ngay_cap'],
        'so_gplx' => ['Số giấy phép', 'gplx_truoc', 'so'],
        'ngay_cap_gplx' => ['Ngày cấp GPLX', 'gplx_truoc', 'ngay_cap'],
        'ngay_trung_tuyen' => ['Ngày trúng tuyển', 'gplx_sau', 'ngay_trung_tuyen'],
    ];

    private const PHIEN_TTL_PHUT = 30;

    /**
     * OCR một ảnh của một mặt giấy tờ.
     *
     * @return array{mat: string, loai: string, ket_qua: array<string, string>, truong_khac: array<string, string>, canh_bao: string|null, text: string, confidence: float|null}
     */
    public static function doAnh(string $mat, string $base64): array
    {
        if (! isset(self::MAT[$mat])) {
            throw new \InvalidArgumentException('Mặt giấy tờ không hợp lệ.');
        }

        $ocr = GoogleVisionClient::detectText($base64);
        $parsed = GiayToParser::parse($ocr['text']);
        $f = $parsed['fields'];

        $ketQua = [];
        foreach (self::TRUONG as $key => [, $matCuaTruong, $truongParser]) {
            if ($matCuaTruong === $mat && isset($f[$truongParser])) {
                $ketQua[$key] = $f[$truongParser];
            }
        }
        // Thẻ CCCD mẫu 2024 in nơi cư trú ở mặt sau.
        if ($mat === 'cccd_sau' && isset($f['noi_thuong_tru'])) {
            $ketQua['noi_thuong_tru'] = $f['noi_thuong_tru'];
        }

        $loaiCanCo = self::MAT[$mat]['loai'];
        $canhBao = null;
        if ($parsed['type'] !== 'khac' && $parsed['type'] !== $loaiCanCo) {
            $canhBao = 'Ảnh có vẻ là '.($parsed['type'] === 'cccd' ? 'CCCD' : 'GPLX').', không phải '.self::MAT[$mat]['nhan'].'.';
        } elseif ($ketQua === []) {
            $canhBao = 'Chưa đọc được thông tin — chụp gần hơn, đủ sáng, tránh lóa.';
        }

        return [
            'mat' => $mat,
            'loai' => $parsed['type'],
            'ket_qua' => $ketQua,
            'truong_khac' => array_diff_key($f, array_flip(array_column(self::TRUONG, 2))),
            'canh_bao' => $canhBao,
            'text' => $ocr['text'],
            'confidence' => $ocr['confidence'],
        ];
    }

    public static function taoPhien(): string
    {
        $token = Str::random(32);
        Cache::put(self::khoaCache($token), ['ket_qua' => [], 'cap_nhat' => now()->timestamp], now()->addMinutes(self::PHIEN_TTL_PHUT));

        return $token;
    }

    /** @return array{ket_qua: array<string, array<string, mixed>>, cap_nhat: int}|null */
    public static function phien(string $token): ?array
    {
        $data = Cache::get(self::khoaCache($token));

        return is_array($data) ? $data : null;
    }

    /** @param  array<string, mixed>  $ketQuaMat  kết quả doAnh() */
    public static function luuVaoPhien(string $token, array $ketQuaMat): void
    {
        $data = self::phien($token) ?? ['ket_qua' => []];
        unset($ketQuaMat['text']);
        $data['ket_qua'][$ketQuaMat['mat']] = $ketQuaMat;
        $data['cap_nhat'] = now()->timestamp;
        Cache::put(self::khoaCache($token), $data, now()->addMinutes(self::PHIEN_TTL_PHUT));
    }

    /**
     * Học viên PMGPLX khớp số căn cước (hoặc họ tên + ngày sinh), kèm giá trị đang lưu để so với kết quả dò.
     *
     * @return list<array{ma_dk: string, ma_kh: string, hien_tai: array<string, string>}>
     */
    public static function timHocVien(string $soCccd, string $hoTen = '', string $ngaySinh = '', string $maDk = ''): array
    {
        $maDk = trim($maDk);
        $soCccd = preg_replace('/\D/', '', $soCccd) ?? '';
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->select([
                'n.MaDK', 'n.HoVaTen', 'n.HoDemNLX', 'n.TenNLX', 'n.NgaySinh', 'n.SoCMT', 'n.NgayCapCMT',
                'n.NoiTT', 'n.NoiTT_MaDVHC', 'n.NoiCT', 'n.NoiCT_MaDVHC',
                'h.MaKhoaHoc', 'h.SoGPLXDaCo', 'h.NgayCapGPLXDaCo', 'h.NgayTTGPLXDaCo',
            ]);

        if ($maDk !== '') {
            $query->where('n.MaDK', $maDk);
        } elseif ($soCccd !== '') {
            $query->where(fn ($q) => $q->where('n.SoCMT', $soCccd)->orWhere('n.SO_CMND_CU', $soCccd));
        } elseif (trim($hoTen) !== '' && $ngaySinh !== '') {
            try {
                $ns = Carbon::createFromFormat('d/m/Y', $ngaySinh)->format('Ymd');
            } catch (\Throwable) {
                return [];
            }
            $query->where('n.HoVaTen', trim($hoTen))->where('n.NgaySinh', $ns);
        } else {
            return [];
        }

        $rows = $query->orderByDesc('n.MaDK')->limit(10)->get();
        $dvhc = DmDvhcTenDayDu::mapForHocVien($rows);
        $daLuu = HocVienDoThongTin::query()
            ->with('nguoiCapNhat:Id,Ten')
            ->whereIn('MaDK', $rows->pluck('MaDK')->map(fn ($m) => (string) $m)->all() ?: [''])
            ->get()
            ->keyBy('MaDK');

        return $rows->map(fn ($n): array => [
            'ma_dk' => (string) $n->MaDK,
            'ma_kh' => (string) ($n->MaKhoaHoc ?? ''),
            'da_luu' => isset($daLuu[(string) $n->MaDK]) ? $daLuu[(string) $n->MaDK]->giaTri() : [],
            'luu_luc' => isset($daLuu[(string) $n->MaDK])
                ? trim($daLuu[(string) $n->MaDK]->NgayCapNhat?->copy()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                    .' '.($daLuu[(string) $n->MaDK]->nguoiCapNhat?->Ten ?? ''))
                : '',
            'hien_tai' => [
                'ho_ten' => trim((string) ($n->HoVaTen ?: trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? '')))),
                'so_cccd' => (string) ($n->SoCMT ?? ''),
                'ngay_sinh' => NgayVn::format($n->NgaySinh ?? null, 'yyyymmdd'),
                'noi_thuong_tru' => DmDvhcTenDayDu::diaChiTuNguoiLx($n, $dvhc)['thuong_tru'],
                'ngay_cap_cccd' => self::ngayDatetime($n->NgayCapCMT ?? null),
                'so_gplx' => trim((string) ($n->SoGPLXDaCo ?? '')),
                'ngay_cap_gplx' => NgayVn::format($n->NgayCapGPLXDaCo ?? null, 'yyyymmdd'),
                'ngay_trung_tuyen' => NgayVn::format($n->NgayTTGPLXDaCo ?? null, 'yyyymmdd'),
            ],
        ])->values()->all();
    }

    /**
     * Lưu các trường đã tích chọn vào MANHLINH (HocVienDoThongTin) — trường không gửi lên giữ nguyên giá trị cũ.
     *
     * @param  array<string, string>  $truong  khoá modal => giá trị (ngày dạng dd/mm/yyyy)
     */
    public static function luu(string $maDk, array $truong, ?int $nguoiId): HocVienDoThongTin
    {
        $hv = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('n.MaDK', $maDk)
            ->first(['n.MaDK', 'h.MaKhoaHoc']);
        if ($hv === null) {
            throw new \InvalidArgumentException('Không tìm thấy học viên '.$maDk.' trên PMGPLX.');
        }

        $data = [];
        foreach ($truong as $key => $value) {
            if (! isset(HocVienDoThongTin::COT[$key])) {
                continue;
            }
            [$cot, $laNgay] = HocVienDoThongTin::COT[$key];
            $value = trim((string) $value);
            if ($value === '') {
                $data[$cot] = null;

                continue;
            }
            if ($laNgay) {
                // Không dùng createFromFormat: nó tự tràn ngày (31/02 → 03/03).
                if (! preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m) || ! checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                    throw new \InvalidArgumentException(self::TRUONG[$key][0].': ngày không hợp lệ «'.$value.'» (cần dd/mm/yyyy).');
                }
                $data[$cot] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            } else {
                $data[$cot] = $key === 'so_cccd' || $key === 'so_gplx' ? preg_replace('/\s+/', '', $value) : $value;
            }
        }
        if ($data === []) {
            throw new \InvalidArgumentException('Chưa chọn trường nào để lưu.');
        }

        $now = now();
        $ban = HocVienDoThongTin::query()->firstOrNew(['MaDK' => $maDk]);
        if (! $ban->exists) {
            $ban->NgayTao = $now;
        }
        $ban->fill($data + [
            'MaKhoaHoc' => $hv->MaKhoaHoc,
            'NguoiCapNhatId' => $nguoiId,
            'NgayCapNhat' => $now,
        ]);
        $ban->save();

        return $ban;
    }

    /** Cột datetime (vd. NguoiLX.NgayCapCMT) → dd/mm/yyyy. */
    public static function ngayDatetime(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function khoaCache(string $token): string
    {
        return 'do-thong-tin:'.$token;
    }
}
