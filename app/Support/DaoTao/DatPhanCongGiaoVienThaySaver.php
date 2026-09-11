<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongGiaoVienThay;
use App\Models\DaoTao\DatPhanCongHocVien;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

class DatPhanCongGiaoVienThaySaver
{
    /**
     * @return array{item: DatPhanCongGiaoVienThay}
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public static function create(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        string $maGiaoVienThay,
        string $tuNgay,
        ?string $denNgay = null
    ): array {
        $maKhoaHoc = trim($maKhoaHoc);
        $maGiaoVienGoc = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc);
        $maGiaoVienThay = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienThay);
        $tuNgayDate = self::parseDate($tuNgay, 'tu_ngay');
        $denNgayDate = $denNgay !== null && trim($denNgay) !== ''
            ? self::parseDate($denNgay, 'den_ngay')
            : null;

        if ($maKhoaHoc === '') {
            throw ValidationException::withMessages(['ma_khoa_hoc' => 'Chọn mã khóa học.']);
        }

        if ($maGiaoVienGoc === '') {
            throw ValidationException::withMessages(['ma_giao_vien_goc' => 'Chọn giáo viên được dạy thay.']);
        }

        if ($maGiaoVienThay === '') {
            throw ValidationException::withMessages(['ma_giao_vien' => 'Nhập mã giáo viên dạy thay.']);
        }

        if (! DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaGiaoVien', $maGiaoVienGoc)
            ->exists()) {
            throw ValidationException::withMessages([
                'ma_giao_vien_goc' => 'Không có phân công nào với GV này trong khóa đã chọn.',
            ]);
        }

        if ($denNgayDate !== null && $denNgayDate->lt($tuNgayDate)) {
            throw ValidationException::withMessages(['den_ngay' => 'Đến ngày phải sau hoặc bằng từ ngày.']);
        }

        self::assertNoOverlap($maKhoaHoc, $maGiaoVienGoc, $tuNgayDate->toDateString(), $denNgayDate?->toDateString());

        $item = DatPhanCongGiaoVienThay::query()->create([
            'MaKhoaHoc' => $maKhoaHoc,
            'MaGiaoVienGoc' => $maGiaoVienGoc,
            'MaGiaoVien' => $maGiaoVienThay,
            'TuNgay' => $tuNgayDate->toDateString(),
            'DenNgay' => $denNgayDate?->toDateString(),
            'NgayNhap' => Carbon::now(),
        ]);

        return ['item' => $item];
    }

    public static function delete(int $id): void
    {
        $item = DatPhanCongGiaoVienThay::query()->find($id);
        if ($item === null) {
            throw ValidationException::withMessages(['id' => 'Bản ghi dạy thay không còn tồn tại.']);
        }

        $item->delete();
    }

    public static function deleteByKhoaHoc(string $maKhoaHoc): void
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return;
        }

        DatPhanCongGiaoVienThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->delete();
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
        string $tuNgay,
        ?string $denNgay
    ): void {
        $existing = DatPhanCongGiaoVienThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaGiaoVienGoc', $maGiaoVienGoc)
            ->get(['TuNgay', 'DenNgay']);

        foreach ($existing as $row) {
            $existingTu = Carbon::parse($row->TuNgay)->toDateString();
            $existingDen = $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null;

            if (self::rangesOverlap($tuNgay, $denNgay, $existingTu, $existingDen)) {
                throw ValidationException::withMessages([
                    'tu_ngay' => 'Khoảng ngày trùng với giáo viên dạy thay đã có.',
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
