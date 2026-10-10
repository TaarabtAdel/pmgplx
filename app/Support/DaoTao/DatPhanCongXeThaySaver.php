<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\DaoTao\DatPhanCongXeThay;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

class DatPhanCongXeThaySaver
{
    /**
     * @return array{item: DatPhanCongXeThay}
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public static function create(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        string $bienSoXeGoc,
        string $bienSoXeThay,
        string $tuNgay,
        ?string $denNgay = null
    ): array {
        $maKhoaHoc = trim($maKhoaHoc);
        $maGiaoVienGoc = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc);
        $bienSoXeGoc = DatPhanCongHocVienSaver::normalizeBienSo($bienSoXeGoc);
        $bienSoXeThay = DatPhanCongHocVienSaver::normalizeBienSo($bienSoXeThay);
        $tuNgayDate = self::parseDate($tuNgay, 'tu_ngay');
        $denNgayDate = $denNgay !== null && trim($denNgay) !== ''
            ? self::parseDate($denNgay, 'den_ngay')
            : null;

        if ($maKhoaHoc === '') {
            throw ValidationException::withMessages(['ma_khoa_hoc' => 'Chọn mã khóa học.']);
        }

        if ($maGiaoVienGoc === '') {
            throw ValidationException::withMessages(['ma_giao_vien_goc' => 'Chọn giáo viên chính.']);
        }

        if ($bienSoXeGoc === '') {
            throw ValidationException::withMessages(['bien_so_xe_goc' => 'Chọn xe gốc.']);
        }

        if ($bienSoXeThay === '') {
            throw ValidationException::withMessages(['bien_so_xe' => 'Nhập biển số xe thay.']);
        }

        $hasAssignment = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaGiaoVien', $maGiaoVienGoc)
            ->get(['BienSoXe'])
            ->contains(
                static fn (DatPhanCongHocVien $row): bool => DatPhanCongHocVienSaver::normalizeBienSo((string) $row->BienSoXe) === $bienSoXeGoc
            );

        if (! $hasAssignment) {
            throw ValidationException::withMessages([
                'bien_so_xe_goc' => 'Không có phân công nào với khóa + GV + xe gốc đã chọn.',
            ]);
        }

        if ($denNgayDate !== null && $denNgayDate->lt($tuNgayDate)) {
            throw ValidationException::withMessages(['den_ngay' => 'Đến ngay phải sau hoặc bằng từ ngày.']);
        }

        DatGiaoVienKhoaTeachingSpan::assertSubstituteDatesWithinTeaching(
            $maKhoaHoc,
            $maGiaoVienGoc,
            $tuNgayDate,
            $denNgayDate
        );

        self::assertNoOverlap($maKhoaHoc, $maGiaoVienGoc, $bienSoXeGoc, $tuNgayDate->toDateString(), $denNgayDate?->toDateString());

        $item = DatPhanCongXeThay::query()->create([
            'MaKhoaHoc' => $maKhoaHoc,
            'MaGiaoVienGoc' => $maGiaoVienGoc,
            'BienSoXeGoc' => $bienSoXeGoc,
            'BienSoXe' => $bienSoXeThay,
            'TuNgay' => $tuNgayDate->toDateString(),
            'DenNgay' => $denNgayDate?->toDateString(),
            'NgayNhap' => Carbon::now(),
        ]);

        return ['item' => $item];
    }

    /**
     * @return array{gv_bien_lich_reverted: int, xe_lich_reverted: int}
     */
    public static function delete(int $id): array
    {
        $item = DatPhanCongXeThay::query()->find($id);
        if ($item === null) {
            throw ValidationException::withMessages(['id' => 'Bản ghi đổi xe không còn tồn tại.']);
        }

        $lich = (new DatPhanCongXeThayLichApplier())->revertKhaiBao($item);
        $item->delete();

        return [
            'gv_bien_lich_reverted' => (int) ($lich['gv_bien_updated'] ?? 0),
            'xe_lich_reverted' => (int) ($lich['xe_updated'] ?? 0),
        ];
    }

    public static function deleteByKhoaHoc(string $maKhoaHoc): void
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return;
        }

        $applier = new DatPhanCongXeThayLichApplier();
        DatPhanCongXeThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('Id')
            ->each(function (DatPhanCongXeThay $row) use ($applier): void {
                $applier->revertKhaiBao($row);
                $row->delete();
            });
    }

    private static function parseDate(string $value, string $field): Carbon
    {
        try {
            return Carbon::parse(trim($value))->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'Ngày không hợp lệ.']);
        }
    }

    private static function assertNoOverlap(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        string $bienSoXeGoc,
        string $tuNgay,
        ?string $denNgay
    ): void {
        $existing = DatPhanCongXeThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaGiaoVienGoc', $maGiaoVienGoc)
            ->where('BienSoXeGoc', $bienSoXeGoc)
            ->get(['TuNgay', 'DenNgay']);

        foreach ($existing as $row) {
            $existingTu = Carbon::parse($row->TuNgay)->toDateString();
            $existingDen = $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null;

            if (self::rangesOverlap($tuNgay, $denNgay, $existingTu, $existingDen)) {
                throw ValidationException::withMessages([
                    'tu_ngay' => 'Khoảng ngày trùng với xe thay đã có.',
                ]);
            }
        }
    }

    private static function rangesOverlap(
        string $startA,
        ?string $endA,
        string $startB,
        ?string $endB
    ): bool {
        $endAValue = $endA ?? '9999-12-31';
        $endBValue = $endB ?? '9999-12-31';

        return $startA <= $endBValue && $startB <= $endAValue;
    }
}
