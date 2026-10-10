<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\GiaoVien as ManhlinhGiaoVien;
use App\Models\DaoTao\KhoaDaoTao;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DatGiaoVienKhoaTeachingSpan
{
    /**
     * Khoảng thời gian GV được phân công dạy (thực hành) trên khóa — không tính khai báo dạy thay.
     *
     * @return list<array{tu_ngay: string, den_ngay: string|null, nguon: string}>
     */
    /**
     * Bao trùm mọi khoảng đang dạy (min từ — max đến; max null nếu có khoảng chưa có ngày kết thúc).
     *
     * @return array{tu_ngay: string, den_ngay: string|null}|null
     */
    public static function envelopeForCourseGiaoVien(string $maKhoaHoc, string $maGiaoVien): ?array
    {
        $spans = self::spansForCourseGiaoVien($maKhoaHoc, $maGiaoVien);
        if ($spans === []) {
            return null;
        }

        $min = null;
        $max = null;
        $openEnd = false;

        foreach ($spans as $span) {
            $tu = $span['tu_ngay'];
            if ($min === null || $tu < $min) {
                $min = $tu;
            }

            $den = $span['den_ngay'] ?? null;
            if ($den === null) {
                $openEnd = true;
            } elseif ($max === null || $den > $max) {
                $max = $den;
            }
        }

        if ($min === null) {
            return null;
        }

        return [
            'tu_ngay' => $min,
            'den_ngay' => $openEnd ? null : $max,
        ];
    }

    /**
     * @throws ValidationException
     */
    public static function assertSubstituteDatesWithinTeaching(
        string $maKhoaHoc,
        string $maGiaoVienGoc,
        Carbon $tuNgay,
        ?Carbon $denNgay
    ): void {
        $bounds = self::envelopeForCourseGiaoVien($maKhoaHoc, $maGiaoVienGoc);
        if ($bounds === null) {
            throw ValidationException::withMessages([
                'tu_ngay' => 'Chưa xác định khoảng đang dạy khóa của GV — không thể thêm dạy thay.',
            ]);
        }

        $min = Carbon::parse($bounds['tu_ngay'])->startOfDay();
        $max = $bounds['den_ngay'] !== null
            ? Carbon::parse($bounds['den_ngay'])->startOfDay()
            : null;

        $rangeLabel = self::formatRangeLabel($bounds['tu_ngay'], $bounds['den_ngay']);

        if ($tuNgay->lt($min) || ($max !== null && $tuNgay->gt($max))) {
            throw ValidationException::withMessages([
                'tu_ngay' => 'Từ ngày phải nằm trong khoảng đang dạy khóa ('.$rangeLabel.').',
            ]);
        }

        if ($denNgay !== null) {
            if ($denNgay->lt($min) || ($max !== null && $denNgay->gt($max))) {
                throw ValidationException::withMessages([
                    'den_ngay' => 'Đến ngày phải nằm trong khoảng đang dạy khóa ('.$rangeLabel.').',
                ]);
            }
        }
    }

    public static function formatRangeLabel(string $tuNgay, ?string $denNgay): string
    {
        try {
            $tu = Carbon::parse($tuNgay)->format('d/m/Y');
        } catch (\Throwable) {
            $tu = $tuNgay;
        }

        if ($denNgay === null || $denNgay === '') {
            return $tu.' – …';
        }

        try {
            $den = Carbon::parse($denNgay)->format('d/m/Y');
        } catch (\Throwable) {
            $den = $denNgay;
        }

        return $tu.' – '.$den;
    }

    public static function spansForCourseGiaoVien(string $maKhoaHoc, string $maGiaoVien): array
    {
        $maKhoaHoc = trim($maKhoaHoc);
        $maGiaoVien = DatPhanCongHocVienSaver::normalizeMaGiaoVien(trim($maGiaoVien));
        if ($maKhoaHoc === '' || $maGiaoVien === '') {
            return [];
        }

        $fromPhanCong = self::spansFromPhanCongDaoTao($maKhoaHoc, $maGiaoVien);
        if ($fromPhanCong !== []) {
            return $fromPhanCong;
        }

        $fromLich = self::spanFromLichThucHanh($maKhoaHoc, $maGiaoVien);
        if ($fromLich !== null) {
            return [$fromLich];
        }

        $fromKhoa = self::spanFromKhoaHoc($maKhoaHoc);
        if ($fromKhoa !== null) {
            return [$fromKhoa];
        }

        return [];
    }

    /**
     * @return list<array{tu_ngay: string, den_ngay: string|null, nguon: string}>
     */
    private static function spansFromPhanCongDaoTao(string $maKhoaHoc, string $maGiaoVien): array
    {
        $khoaIds = self::resolveKhoaDaoTaoIds($maKhoaHoc);
        if ($khoaIds === []) {
            return [];
        }

        $giaoVienId = ManhlinhGiaoVien::query()
            ->where('MaGV', $maGiaoVien)
            ->value('Id');
        if ($giaoVienId === null) {
            return [];
        }

        $rows = PhanCongDaoTao::query()
            ->whereIn('KhoaDaoTaoId', $khoaIds)
            ->where('GiaoVienId', (int) $giaoVienId)
            ->where('LoaiGiangDay', 'thuc_hanh')
            ->whereNotNull('TuNgay')
            ->orderBy('TuNgay')
            ->orderBy('Id')
            ->get(['TuNgay', 'DenNgay']);

        $spans = [];
        foreach ($rows as $row) {
            $tu = self::formatDate($row->TuNgay);
            if ($tu === null) {
                continue;
            }
            $spans[] = [
                'tu_ngay' => $tu,
                'den_ngay' => self::formatDate($row->DenNgay),
                'nguon' => 'phan_cong_dao_tao',
            ];
        }

        return $spans;
    }

    /**
     * @return array{tu_ngay: string, den_ngay: string|null, nguon: string}|null
     */
    private static function spanFromLichThucHanh(string $maKhoaHoc, string $maGiaoVien): ?array
    {
        $rows = KhoaHocGiaoVien::query()
            ->where('MaKH', $maKhoaHoc)
            ->where('LoaiGV', 'TH')
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD')
            ->get(['MaGV', 'NgayBD', 'NgayKT']);

        $min = null;
        $max = null;

        foreach ($rows as $row) {
            $maGvRow = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($row->MaGV ?? ''));
            if ($maGvRow !== $maGiaoVien) {
                continue;
            }

            try {
                $start = Carbon::parse($row->NgayBD)->toDateString();
                $end = Carbon::parse($row->NgayKT ?? $row->NgayBD)->toDateString();
            } catch (\Throwable) {
                continue;
            }

            if ($min === null || $start < $min) {
                $min = $start;
            }
            if ($max === null || $end > $max) {
                $max = $end;
            }
        }

        if ($min === null) {
            return null;
        }

        return [
            'tu_ngay' => $min,
            'den_ngay' => $max,
            'nguon' => 'lich_pmgplx',
        ];
    }

    /**
     * @return array{tu_ngay: string, den_ngay: string|null, nguon: string}|null
     */
    private static function spanFromKhoaHoc(string $maKhoaHoc): ?array
    {
        $khoa = KhoaHoc::query()->find($maKhoaHoc);
        if ($khoa === null) {
            return null;
        }

        $tu = self::formatDate($khoa->NgayKG ?? null);
        if ($tu === null) {
            return null;
        }

        return [
            'tu_ngay' => $tu,
            'den_ngay' => self::formatDate($khoa->NgayBG ?? null),
            'nguon' => 'khoa_hoc',
        ];
    }

    /**
     * @return list<int>
     */
    private static function resolveKhoaDaoTaoIds(string $maKhoaHoc): array
    {
        $ids = KhoaDaoTao::query()
            ->where('MaKhoa', $maKhoaHoc)
            ->pluck('Id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($ids !== []) {
            return array_values(array_unique($ids));
        }

        $tenKh = trim((string) (KhoaHoc::query()->where('MaKH', $maKhoaHoc)->value('TenKH') ?? ''));
        if ($tenKh === '') {
            return [];
        }

        $tenNorm = KhoaDaoTao::normalizeTenKhoa($tenKh);

        return KhoaDaoTao::query()
            ->get(['Id', 'TenKhoa'])
            ->filter(fn (KhoaDaoTao $k): bool => KhoaDaoTao::normalizeTenKhoa((string) $k->TenKhoa) === $tenNorm)
            ->pluck('Id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private static function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ($value instanceof Carbon ? $value : Carbon::parse($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
