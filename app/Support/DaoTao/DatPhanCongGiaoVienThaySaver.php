<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatPhanCongGiaoVienThay;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Support\PMGPLX\GiaoVienLichCrossKhoaChecker;
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
        ?string $denNgay = null,
        ?string $tuGio = null,
        ?string $denGio = null
    ): array {
        $maKhoaHoc = trim($maKhoaHoc);
        $maGiaoVienGoc = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienGoc);
        $maGiaoVienThay = DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVienThay);
        $tuNgayDate = self::parseDate($tuNgay, 'tu_ngay');
        $denNgayDate = $denNgay !== null && trim($denNgay) !== ''
            ? self::parseDate($denNgay, 'den_ngay')
            : null;
        $tuGio = self::parseGio($tuGio, 'tu_gio');
        $denGio = self::parseGio($denGio, 'den_gio');

        if ($tuGio !== null && $denGio !== null && $denGio <= $tuGio) {
            throw ValidationException::withMessages(['den_gio' => 'Giờ kết thúc phải sau giờ bắt đầu.']);
        }

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

        DatGiaoVienKhoaTeachingSpan::assertSubstituteDatesWithinTeaching(
            $maKhoaHoc,
            $maGiaoVienGoc,
            $tuNgayDate,
            $denNgayDate
        );

        self::assertNoOverlap(
            $maKhoaHoc,
            $maGiaoVienGoc,
            $tuNgayDate->toDateString(),
            $denNgayDate?->toDateString(),
            $tuGio,
            $denGio
        );

        GiaoVienLichCrossKhoaChecker::assertSubstituteThayHasNoCrossKhoaConflict(
            $maKhoaHoc,
            $maGiaoVienGoc,
            $maGiaoVienThay,
            $tuNgayDate,
            $denNgayDate,
            $tuGio,
            $denGio
        );

        $item = DatPhanCongGiaoVienThay::query()->create([
            'MaKhoaHoc' => $maKhoaHoc,
            'MaGiaoVienGoc' => $maGiaoVienGoc,
            'MaGiaoVien' => $maGiaoVienThay,
            'TuNgay' => $tuNgayDate->toDateString(),
            'DenNgay' => $denNgayDate?->toDateString(),
            'TuGio' => $tuGio,
            'DenGio' => $denGio,
            'NgayNhap' => Carbon::now(),
        ]);

        return ['item' => $item];
    }

    /**
     * @return array{gv_lich_reverted: int, xe_lich_reverted: int}
     */
    public static function delete(int $id): array
    {
        $item = DatPhanCongGiaoVienThay::query()->find($id);
        if ($item === null) {
            throw ValidationException::withMessages(['id' => 'Bản ghi dạy thay không còn tồn tại.']);
        }

        $lich = (new DatPhanCongGiaoVienThayLichApplier())->revertKhaiBao($item);
        $item->delete();

        return [
            'gv_lich_reverted' => (int) ($lich['gv_updated'] ?? 0),
            'xe_lich_reverted' => (int) ($lich['xe_updated'] ?? 0),
        ];
    }

    public static function deleteByKhoaHoc(string $maKhoaHoc): void
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return;
        }

        $applier = new DatPhanCongGiaoVienThayLichApplier();
        DatPhanCongGiaoVienThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('Id')
            ->each(function (DatPhanCongGiaoVienThay $row) use ($applier): void {
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

    private static function parseGio(?string $value, string $field): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $gio = DatPhanCongGiaoVienThay::gioHienThi($value);
        if ($gio === null || $gio > '23:59' || (int) substr($gio, 3, 2) > 59) {
            throw ValidationException::withMessages([$field => 'Giờ không hợp lệ (định dạng HH:MM).']);
        }

        return $gio;
    }

    private static function assertNoOverlap(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        string $tuNgay,
        ?string $denNgay,
        ?string $tuGio = null,
        ?string $denGio = null
    ): void {
        $existing = DatPhanCongGiaoVienThay::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->where('MaGiaoVienGoc', $maGiaoVienGoc)
            ->get(['TuNgay', 'DenNgay', 'TuGio', 'DenGio']);

        foreach ($existing as $row) {
            $existingTu = Carbon::parse($row->TuNgay)->toDateString();
            $existingDen = $row->DenNgay !== null ? Carbon::parse($row->DenNgay)->toDateString() : null;

            if (! self::rangesOverlap($tuNgay, $denNgay, $existingTu, $existingDen)) {
                continue;
            }
            if (! DatPhanCongGiaoVienThayResolver::khungGioGiaoNhau($tuGio, $denGio, $row->TuGio, $row->DenGio)) {
                continue;
            }

            throw ValidationException::withMessages([
                'tu_ngay' => sprintf(
                    'Trùng với khai báo dạy thay đã có: %s – %s, %s.',
                    Carbon::parse($existingTu)->format('d/m/Y'),
                    $existingDen !== null ? Carbon::parse($existingDen)->format('d/m/Y') : '…',
                    DatPhanCongGiaoVienThayResolver::nhanKhungGio($row->TuGio, $row->DenGio)
                ),
            ]);
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
