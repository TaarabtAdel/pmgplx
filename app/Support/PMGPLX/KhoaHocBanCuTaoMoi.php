<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLXOLD\KhoaHoc as KhoaHocOld;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Tạo khoá trên PMGPLX bản cũ — MaKH nhập tay (PK không identity). */
final class KhoaHocBanCuTaoMoi
{
    private const CONNECTION = 'sqlsrv_old';

    /**
     * @param  array{ma_kh: string, ten_kh: string, hang_gplx?: ?string, hang_dt?: ?string, ngay_kg?: ?string, ngay_bg?: ?string, trang_thai?: bool}  $input
     */
    public function tao(array $input): KhoaHocOld
    {
        $maKh = trim($input['ma_kh']);
        $tenKh = trim($input['ten_kh']);

        if ($maKh === '' || $tenKh === '') {
            throw new \InvalidArgumentException('Mã khoá và tên khoá là bắt buộc.');
        }

        if (KhoaHocOld::query()->where('MaKH', $maKh)->exists()) {
            throw new \InvalidArgumentException('Mã khoá «'.$maKh.'» đã tồn tại trên bản cũ.');
        }

        $allowed = CopyBanCuColumnLists::khoaHocOnOld();
        if ($allowed === []) {
            throw new \RuntimeException('Không kết nối được bảng KhoaHoc trên PMGPLX bản cũ.');
        }

        $mau = KhoaHocOld::query()->orderByDesc('NgayKG')->orderByDesc('MaKH')->first();
        $now = Carbon::now();
        $user = 'WEB';
        $auth = auth()->user();
        if ($auth !== null) {
            $user = trim((string) ($auth->Ten ?? $auth->name ?? $auth->email ?? 'WEB')) ?: 'WEB';
        }

        $payload = [
            'MaKH' => $maKh,
            'TenKH' => $tenKh,
            'HangGPLX' => trim((string) ($input['hang_gplx'] ?? '')) ?: null,
            'HangDT' => trim((string) ($input['hang_dt'] ?? '')) ?: null,
            'NgayKG' => ! empty($input['ngay_kg']) ? Carbon::parse($input['ngay_kg'])->startOfDay() : null,
            'NgayBG' => ! empty($input['ngay_bg']) ? Carbon::parse($input['ngay_bg'])->startOfDay() : null,
            'TongSoHV' => 0,
            'ThoiGianDT' => 0,
            'TrangThai' => array_key_exists('trang_thai', $input)
                ? (bool) $input['trang_thai']
                : true,
            'NguoiTao' => $user,
            'NgayTao' => $now,
            'NguoiSua' => $user,
            'NgaySua' => $now,
        ];

        if ($mau !== null) {
            foreach (['MaCSDT', 'MaSoGTVT', 'MucTieuDT', 'TT_Xuly'] as $col) {
                if (isset($mau->{$col}) && $mau->{$col} !== null && $mau->{$col} !== '') {
                    $payload[$col] = $mau->{$col};
                }
            }
        }

        $insert = [];
        foreach ($allowed as $col) {
            if (! array_key_exists($col, $payload)) {
                continue;
            }
            $insert[$col] = $payload[$col];
        }

        DB::connection(self::CONNECTION)->table('KhoaHoc')->insert($insert);

        return KhoaHocOld::query()->findOrFail($maKh);
    }
}