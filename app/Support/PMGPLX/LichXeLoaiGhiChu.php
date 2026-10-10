<?php

namespace App\Support\PMGPLX;

/**
 * Loại lịch xe suy từ cột GhiChu — cùng logic màn /pmgplx/lich/xe-tap (+ Hình, Ôn tập).
 */
class LichXeLoaiGhiChu
{
    public const LOAI_CAO_TOC = 'cao_toc';

    public const LOAI_BAN_DEM = 'ban_dem';

    public const LOAI_HINH = 'hinh';

    public const LOAI_ON_TAP = 'on_tap';

    public const LOAI_TU_DONG = 'tu_dong';

    public const LOAI_CA_BIN = 'ca_bin';

    public const LOAI_DAT = 'dat';

    /** Ẩn ngày cặp GV không có buổi xe (nghỉ / xin nghỉ) — màn dò lịch trống GV. */
    public const HIDE_NGAY_NGHI_TRONG_LICH = 'ngay_nghi_trong_lich';

    /** @deprecated URL cũ — cùng ý nghĩa với {@see HIDE_NGAY_NGHI_TRONG_LICH} */
    public const HIDE_THU_5_TRONG_LICH = 'thu_5_trong_lich';

    /** @return list<string> */
    public static function allowedFilters(): array
    {
        return [
            self::LOAI_CAO_TOC,
            self::LOAI_BAN_DEM,
            self::LOAI_HINH,
            self::LOAI_ON_TAP,
            self::LOAI_TU_DONG,
            self::LOAI_CA_BIN,
            self::LOAI_DAT,
        ];
    }

    /**
     * Loại xe + tuỳ chọn ẩn ngày (checkbox «Bỏ qua loại» trên dò lịch trống).
     *
     * @return list<string>
     */
    public static function allowedDoTrongHideDayFilters(): array
    {
        return array_merge(self::allowedFilters(), [self::HIDE_NGAY_NGHI_TRONG_LICH]);
    }

    public static function normalizeFilter(?string $loai): string
    {
        $loai = trim((string) $loai);
        if ($loai === self::HIDE_NGAY_NGHI_TRONG_LICH || $loai === self::HIDE_THU_5_TRONG_LICH) {
            return $loai === self::HIDE_THU_5_TRONG_LICH
                ? self::HIDE_NGAY_NGHI_TRONG_LICH
                : $loai;
        }

        return in_array($loai, self::allowedFilters(), true) ? $loai : '';
    }

    /**
     * @return list<string>
     */
    public static function normalizeFilters(mixed $loai): array
    {
        if (is_string($loai)) {
            $one = self::normalizeFilter($loai);

            return $one === '' ? [] : [$one];
        }
        if (! is_array($loai)) {
            return [];
        }

        $out = [];
        foreach ($loai as $item) {
            $n = self::normalizeFilter(is_string($item) ? $item : '');
            if ($n !== '' && ! in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function normalizeDoTrongHideDayFilters(mixed $raw): array
    {
        return self::normalizeFilters($raw);
    }

    /**
     * @return array{loai: list<string>, an_ngay_nghi_trong_lich: bool}
     */
    public static function parseDoTrongHideDayFilters(mixed $raw): array
    {
        $all = self::normalizeDoTrongHideDayFilters($raw);
        $loai = [];
        $anNgayNghi = false;
        foreach ($all as $f) {
            if ($f === self::HIDE_NGAY_NGHI_TRONG_LICH) {
                $anNgayNghi = true;

                continue;
            }
            $loai[] = $f;
        }

        return [
            'loai' => $loai,
            'an_ngay_nghi_trong_lich' => $anNgayNghi,
        ];
    }

    /**
     * @param  list<string>  $filters
     */
    public static function filtersLabel(array $filters): string
    {
        $filters = self::normalizeFilters($filters);
        if ($filters === []) {
            return 'Tất cả';
        }

        return implode(', ', array_map(
            static fn (string $f): string => self::filterLabel($f),
            $filters
        ));
    }

    public static function filterLabel(string $loaiFilter): string
    {
        return match (self::normalizeFilter($loaiFilter)) {
            self::LOAI_CAO_TOC => 'Cao Tốc',
            self::LOAI_BAN_DEM => 'Ban Đêm',
            self::LOAI_HINH => 'Hình',
            self::LOAI_ON_TAP => 'Ôn tập',
            self::LOAI_TU_DONG => 'Tự động',
            self::LOAI_CA_BIN => 'Ca bin',
            self::LOAI_DAT => 'Dat',
            self::HIDE_NGAY_NGHI_TRONG_LICH => 'Ngày nghỉ (lịch trống)',
            default => 'Tất cả',
        };
    }

    /**
     * Nhãn hiển thị Loại từ ghi chú (một buổi).
     */
    public static function loaiLabelFromGhiChu(?string $ghiChu): string
    {
        $loai = self::detectLoaiKeyFromGhiChu($ghiChu);

        return match ($loai) {
            self::LOAI_CAO_TOC => 'Cao Tốc',
            self::LOAI_BAN_DEM => 'Ban Đêm',
            self::LOAI_HINH => 'Hình',
            self::LOAI_ON_TAP => 'Ôn tập',
            self::LOAI_TU_DONG => 'Tự động',
            self::LOAI_CA_BIN => 'Ca bin',
            self::LOAI_DAT => 'Dat',
            default => '—',
        };
    }

    public static function detectLoaiKeyFromGhiChu(?string $ghiChu): ?string
    {
        $raw = trim((string) $ghiChu);
        if ($raw === '') {
            return null;
        }

        $t = mb_strtolower($raw);
        if (str_contains($t, 'tự động') || str_contains($t, 'tu dong')) {
            return self::LOAI_TU_DONG;
        }
        if (str_contains($t, 'ca bin')) {
            return self::LOAI_CA_BIN;
        }
        if (str_contains($t, 'ban đêm') || str_contains($t, 'ban dem')) {
            return self::LOAI_BAN_DEM;
        }
        if (mb_stripos($raw, 'hình') !== false || mb_stripos($raw, 'hinh') !== false) {
            return self::LOAI_HINH;
        }
        if (mb_stripos($raw, 'ôn') !== false) {
            return self::LOAI_ON_TAP;
        }
        if (str_contains($t, 'cao tốc') || str_contains($t, 'cao toc')) {
            return self::LOAI_CAO_TOC;
        }
        if (self::ghiChuMatchesDatNeedles($t)) {
            return self::LOAI_DAT;
        }

        return null;
    }

    public static function ghiChuMatchesLoaiFilter(?string $ghiChu, string $loaiFilter): bool
    {
        $loaiFilter = self::normalizeFilter($loaiFilter);
        if ($loaiFilter === '') {
            return true;
        }

        return self::ghiChuContainsAnyNeedle($ghiChu, self::needlesForFilter($loaiFilter));
    }

    /**
     * Ghi chú khớp ít nhất một loại trong danh sách (OR).
     *
     * @param  list<string>  $loaiFilters
     */
    public static function ghiChuMatchesAnyLoaiFilter(?string $ghiChu, array $loaiFilters): bool
    {
        foreach (self::normalizeFilters($loaiFilters) as $filter) {
            if (self::ghiChuMatchesLoaiFilter($ghiChu, $filter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nhãn tiêu chí «bỏ qua loại» trên màn dò lịch trống.
     *
     * @param  list<string>  $filters
     */
    public static function boQuaLoaiFiltersLabel(array $filters): string
    {
        $filters = self::normalizeDoTrongHideDayFilters($filters);
        if ($filters === []) {
            return 'Không ẩn ngày theo tiêu chí';
        }

        return 'Ẩn ngày: '.implode(', ', array_map(
            static fn (string $f): string => self::filterLabel($f),
            $filters
        ));
    }

    private static function ghiChuMatchesDatNeedles(string $lower): bool
    {
        $datOnly = ['dốc', 'doc', 'có tải', 'co tai', 'phức tạp', 'phuc tap'];

        return self::containsAnyInText($lower, $datOnly);
    }

    /**
     * @param  list<string>  $needles
     */
    private static function ghiChuContainsAnyNeedle(?string $ghiChu, array $needles): bool
    {
        $raw = trim((string) $ghiChu);
        if ($raw === '' || $needles === []) {
            return false;
        }

        return self::containsAnyInText(mb_strtolower($raw), $needles);
    }

    /**
     * @param  list<string>  $needles
     */
    private static function containsAnyInText(string $lower, array $needles): bool
    {
        foreach ($needles as $needle) {
            $n = mb_strtolower($needle);
            if ($n === '') {
                continue;
            }
            if (mb_strpos($lower, $n) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function needlesForFilter(string $loaiFilter): array
    {
        return match (self::normalizeFilter($loaiFilter)) {
            self::LOAI_CAO_TOC => ['cao tốc', 'cao toc'],
            self::LOAI_BAN_DEM => ['ban đêm', 'ban dem'],
            self::LOAI_HINH => ['Hình', 'Hinh', 'hình', 'hinh'],
            self::LOAI_ON_TAP => ['Ôn', 'ôn'],
            self::LOAI_TU_DONG => ['tự động', 'Tự động', 'tu dong', 'Tu dong'],
            self::LOAI_CA_BIN => ['ca bin', 'Ca bin', 'CA BIN'],
            self::LOAI_DAT => [
                'dốc', 'doc', 'Dốc', 'Doc',
                'cao tốc', 'cao toc', 'Cao tốc', 'Cao toc',
                'có tải', 'co tai', 'Có tải', 'Co tai',
                'phức tạp', 'phuc tap', 'Phức tạp', 'Phuc tap',
            ],
            default => [],
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  string|list<string>  $loaiFilter
     */
    public static function applyGhiChuLoaiFilter($query, string $ghiChuColumn, string|array $loaiFilter): void
    {
        $filters = is_array($loaiFilter)
            ? self::normalizeFilters($loaiFilter)
            : self::normalizeFilters($loaiFilter);
        if ($filters === []) {
            return;
        }

        $query->where(function ($outer) use ($ghiChuColumn, $filters): void {
            foreach ($filters as $filter) {
                $needles = self::needlesForFilter($filter);
                if ($needles === []) {
                    continue;
                }
                $outer->orWhere(function ($sub) use ($ghiChuColumn, $needles): void {
                    foreach ($needles as $needle) {
                        $sub->orWhere($ghiChuColumn, 'like', '%'.$needle.'%');
                    }
                });
            }
        });
    }

    public static function noiDungFromSlot(?string $ghiChu, ?string $tenMonHoc = null, ?string $nguon = null): string
    {
        $ghiChu = trim((string) $ghiChu);
        if ($ghiChu !== '') {
            return $ghiChu;
        }
        $tenMon = trim((string) $tenMonHoc);
        if ($tenMon !== '') {
            return $tenMon;
        }

        return '—';
    }
}
