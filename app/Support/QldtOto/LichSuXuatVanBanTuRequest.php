<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;

/**
 * Trích số văn bản (công văn / QĐ / TB…) và ngày trên văn bản từ request hoặc session xem trước.
 *
 * @return array{so_cong_van: ?string, ngay_van_ban: ?Carbon, ma_kh: ?string}
 */
final class LichSuXuatVanBanTuRequest
{
    /** @var array<string, array{key: string, sub: ?string}> */
    private const SESSION_PREVIEW = [
        'qldtoto.quyet-dinh-doi-xe.export' => ['key' => 'qldtoto.preview.quyet_dinh_doi_xe', 'sub' => 'preview'],
        'qldtoto.quyet-dinh-doi-giao-vien.export' => ['key' => 'qldtoto.preview.quyet_dinh_doi_giao_vien', 'sub' => 'preview'],
        'qldtoto.quyet-dinh-dieu-dong-xe.export' => ['key' => 'qldtoto.preview.quyet_dinh_dieu_dong_xe', 'sub' => null],
        'qldtoto.so-phan-cong-gv-xe-tap-lai.export' => ['key' => 'qldtoto.preview.so_phan_cong_gv_xe', 'sub' => null],
    ];

    public static function trichXuat(Request $request, string $tenRoute): array
    {
        $data = array_merge(
            $request->query(),
            $request->except(['_token', '_method']),
        );

        $session = self::sessionPreview($request, $tenRoute);
        if ($session !== null) {
            $data = array_merge($session, $data);
        }

        $ngay = self::ngayTuMang($data);
        $so = self::soTuMang($data, $ngay, $tenRoute);
        $maKh = self::chuoiTuMang($data, ['ma_kh', 'ma_khoa_hoc', 'MaKh']);

        if ($maKh === null && $request->route() !== null) {
            $lich = $request->route('lich');
            if (is_object($lich) && isset($lich->TenKhoa)) {
                $tenKhoa = trim((string) $lich->TenKhoa);
                if ($tenKhoa !== '') {
                    $maKh = mb_substr($tenKhoa, 0, 50);
                }
            }
        }

        return [
            'so_cong_van' => $so !== '' ? mb_substr($so, 0, 80) : null,
            'ngay_van_ban' => $ngay,
            'ma_kh' => $maKh !== null ? mb_substr($maKh, 0, 50) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function soTuMang(array $data, ?Carbon $ngay, string $tenRoute): string
    {
        if ($cv = trim((string) ($data['so_cong_van'] ?? ''))) {
            return self::dinhdangTtml($cv, $ngay);
        }
        if ($bc = trim((string) ($data['so_bc'] ?? ''))) {
            return self::dinhdangTtml($bc, $ngay);
        }
        if ($tb = trim((string) ($data['so_tb'] ?? ''))) {
            if ($tenRoute === 'qldtoto.thong-bao-tap-trung-thuc-hanh.export') {
                return $tb.ThongBaoTapTrungThucHanhDocx::HAU_TO_SO_TB;
            }

            return $tb.ThongBaoKiemTraKetThucMonDocx::HAU_TO_SO;
        }
        if ($qd = trim((string) ($data['so_qd'] ?? ''))) {
            return self::dinhdangQd($qd);
        }
        if ($sq = trim((string) ($data['so_quyet_dinh'] ?? ''))) {
            return SoTayQuyetDinhThanhLap::soVaHauTo($sq);
        }

        if (! empty($data['xe_blocks']) && is_array($data['xe_blocks'])) {
            $danh = [];
            foreach ($data['xe_blocks'] as $block) {
                if (! is_array($block)) {
                    continue;
                }
                $raw = QuyetDinhDieuDongXeSupport::soQuyetDinhChiSo((string) ($block['so_quyet_dinh'] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $full = self::dinhdangQd($raw);
                $danh[$full] = true;
            }

            return implode('; ', array_keys($danh));
        }

        return '';
    }

    private static function dinhdangTtml(string $so, ?Carbon $ngay): string
    {
        if (str_contains($so, '/')) {
            return $so;
        }
        $nam = $ngay?->year ?? (int) date('Y');

        return $so.'/TTML-'.$nam;
    }

    private static function dinhdangQd(string $so): string
    {
        if (str_contains($so, '/')) {
            return $so;
        }

        return $so.QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function ngayTuMang(array $data): ?Carbon
    {
        foreach (['ngay_bao_cao', 'ngay_quyet_dinh', 'ngay_lap', 'ngay_thong_bao'] as $key) {
            $v = trim((string) ($data[$key] ?? ''));
            if ($v === '') {
                continue;
            }
            try {
                return Carbon::parse($v)->startOfDay();
            } catch (InvalidFormatException) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function chuoiTuMang(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $v = trim((string) ($data[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private static function sessionPreview(Request $request, string $tenRoute): ?array
    {
        $cfg = self::SESSION_PREVIEW[$tenRoute] ?? null;
        if ($cfg === null) {
            return null;
        }

        $raw = $request->session()->get($cfg['key']);
        if (! is_array($raw)) {
            return null;
        }

        if ($cfg['sub'] !== null) {
            $nested = $raw[$cfg['sub']] ?? null;

            return is_array($nested) ? $nested : null;
        }

        return $raw;
    }
}