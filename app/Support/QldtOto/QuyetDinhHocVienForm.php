<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use Illuminate\Support\Collection;

/** Dùng chung cho các quyết định một học viên (bảo lưu, cho thôi học). */
final class QuyetDinhHocVienForm
{
    /**
     * @return list<array{ma_dk: string, ho_ten: string, ngay_sinh: string, so_cmnd: string, noi_cu_tru: string}>
     */
    public static function hocVien(KhoaHoc $khoa): array
    {
        return array_map(fn (array $hv): array => [
            'ma_dk' => $hv['ma_dk'],
            'ho_ten' => QuyetDinhDoiGiaoVienSupport::chuanHoaHoTen($hv['ho_ten']),
            'ngay_sinh' => $hv['ngay_sinh'],
            'so_cmnd' => $hv['so_cmnd'],
            'noi_cu_tru' => $hv['noi_cu_tru'],
        ], BaoCaoDeNghiSatHachData::hocVien((string) $khoa->MaKH));
    }

    /** @return Collection<int, KhoaHoc> */
    public static function khoaOptions(): Collection
    {
        return KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();
    }

    /** @return array<string, string> */
    public static function macDinhChung(KhoaHoc $khoa): array
    {
        return [
            'ma_kh' => (string) $khoa->MaKH,
            'so_qd' => '',
            'ngay_quyet_dinh' => now()->format('Y-m-d'),
            'ma_dk' => '',
            'ho_ten' => '',
            'ngay_sinh' => '',
            'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
        ];
    }

    /** @return array<string, list<string>> */
    public static function rulesChung(): array
    {
        return [
            'ma_kh' => ['required', 'string', 'max:50'],
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'ma_dk' => ['nullable', 'string', 'max:50'],
            'ho_ten' => ['required', 'string', 'max:120'],
            'ngay_sinh' => ['required', 'string', 'max:20'],
            'hang' => ['required', 'string', 'max:40'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'ly_do' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public static function messagesChung(): array
    {
        return [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
            'ho_ten.required' => 'Chọn học viên (hoặc nhập họ tên).',
        ];
    }
}
