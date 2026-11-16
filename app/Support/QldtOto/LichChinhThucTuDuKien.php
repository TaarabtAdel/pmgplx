<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;
use App\Models\QldtOto\LichChinhThuc;
use App\Models\QldtOto\LichChinhThucBuoi;
use App\Models\QldtOto\LichChinhThucCapGv;
use App\Models\QldtOto\LichChinhThucNgayNghi;
use App\Models\QldtOto\LichDuKienThucHanh;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Sao lịch dự kiến đã sinh → lịch chính thức (chỉnh thứ tự bài / xuất / lưu PMGPLX). */
final class LichChinhThucTuDuKien
{
    public static function tao(LichDuKienThucHanh $duKien, ?string $maKh = null, ?int $nguoiTaoId = null): LichChinhThuc
    {
        $duKien->loadMissing(['ngayNghis', 'capGvs', 'buois']);
        if ($duKien->buois->isEmpty()) {
            throw ValidationException::withMessages([
                'lich_du_kien_id' => 'Lịch dự kiến chưa sinh buổi — mở lịch dự kiến và bấm Sinh lịch trước.',
            ]);
        }

        $maKh = trim((string) $maKh);
        if ($maKh === '') {
            $maKh = self::goiYMaKh($duKien->TenKhoa) ?? '';
        }
        if ($maKh !== '' && ! KhoaHoc::query()->where('MaKH', $maKh)->exists()) {
            throw ValidationException::withMessages([
                'ma_kh' => 'Mã khoá PMGPLX «'.$maKh.'» không có trong danh mục khoá học.',
            ]);
        }

        $now = Carbon::now();

        return DB::connection('sqlsrv_manhlinh')->transaction(function () use ($duKien, $maKh, $nguoiTaoId, $now): LichChinhThuc {
            $lich = LichChinhThuc::query()->create([
                'LichDuKienId' => $duKien->Id,
                'TenKhoa' => $duKien->TenKhoa,
                'MaKH' => $maKh !== '' ? $maKh : null,
                'Hang' => $duKien->Hang,
                'NgayBatDauThucHanh' => $duKien->NgayBatDauThucHanh,
                'XeTuDong' => $duKien->XeTuDong,
                'CabinCapTruocNgay' => $duKien->CabinCapTruocNgay,
                'NguoiTaoId' => $nguoiTaoId,
                'NgayTao' => $now,
                'NgayCapNhat' => $now,
            ]);

            foreach ($duKien->ngayNghis as $n) {
                LichChinhThucNgayNghi::query()->create([
                    'LichChinhThucId' => $lich->Id,
                    'Ngay' => $n->Ngay,
                    'GhiChu' => $n->GhiChu,
                ]);
            }

            foreach ($duKien->capGvs->sortBy('ThuTu') as $c) {
                LichChinhThucCapGv::query()->create([
                    'LichChinhThucId' => $lich->Id,
                    'ThuTu' => $c->ThuTu,
                    'MaGvSang' => $c->MaGvSang,
                    'MaGvChieu' => $c->MaGvChieu,
                    'BienSoXe' => $c->BienSoXe,
                ]);
            }

            $theoCot = $duKien->buois
                ->sortBy(fn ($b) => $b->Ngay->format('Y-m-d').'|'.$b->CapThuTu.'|'.$b->Ca)
                ->groupBy(fn ($b) => $b->CapThuTu.'|'.$b->Ca);

            foreach ($theoCot as $ds) {
                $thuTu = 0;
                foreach ($ds->sortBy(fn ($b) => $b->Ngay->format('Y-m-d')) as $b) {
                    $thuTu++;
                    LichChinhThucBuoi::query()->create([
                        'LichChinhThucId' => $lich->Id,
                        'CapThuTu' => $b->CapThuTu,
                        'Ca' => $b->Ca,
                        'MaGv' => $b->MaGv,
                        'BienSoXe' => $b->BienSoXe,
                        'Ngay' => $b->Ngay,
                        'NgaySo' => $b->NgaySo,
                        'NoiDung' => $b->NoiDung,
                        'GioBatDau' => $b->GioBatDau,
                        'GioKetThuc' => $b->GioKetThuc,
                        'SoGio' => $b->SoGio,
                        'ThuTuBai' => $thuTu,
                    ]);
                }
            }

            return $lich->fresh();
        });
    }

    public static function goiYMaKh(string $tenKhoa): ?string
    {
        $ten = trim($tenKhoa);
        if ($ten === '') {
            return null;
        }

        $exact = KhoaHoc::query()->where('MaKH', $ten)->value('MaKH');
        if (is_string($exact) && $exact !== '') {
            return $exact;
        }

        $byTen = KhoaHoc::query()
            ->where('TenKH', $ten)
            ->orderByDesc('NgayKG')
            ->value('MaKH');

        return is_string($byTen) && $byTen !== '' ? $byTen : null;
    }
}
