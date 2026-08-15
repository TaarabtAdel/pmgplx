<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class TienDoDaoTao extends Model
{
    /** SQL Server giới hạn 2100 tham số / câu INSERT. */
    private const SQLSERVER_MAX_PARAMS = 2100;

    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'TienDoDaoTao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaLop',
        'GiaoVienDay',
        'SoLuongHocVien',
        'SoHocVienTotNghiep',
        'NamHoc',
        'ThangNam',
        'TuanThu',
        'TuNgay',
        'DenNgay',
        'KyHieu',
        'GhiChu',
    ];

    protected $casts = [
        'SoLuongHocVien' => 'integer',
        'SoHocVienTotNghiep' => 'integer',
        'NamHoc' => 'integer',
        'TuanThu' => 'integer',
        'TuNgay' => 'date',
        'DenNgay' => 'date',
    ];

    /**
     * Import: mỗi Mã khóa-lớp (+ năm học) đã có thì xóa dữ liệu cũ rồi ghi mới.
     *
     * @param  array<string, mixed>  $preview
     * @return array{saved: int, updated_classes: int, new_classes: int}
     *
     * @throws Throwable
     */
    public static function upsertFromPreview(array $preview): array
    {
        $rows = self::rowsFromPreview($preview);
        if ($rows === []) {
            return ['saved' => 0, 'updated_classes' => 0, 'new_classes' => 0];
        }

        $classKeys = [];
        foreach ($rows as $row) {
            $maKhoaLop = $row['MaKhoaLop'];
            $namHoc = $row['NamHoc'] ?? null;
            $key = $maKhoaLop.'|'.($namHoc ?? 'null');
            $classKeys[$key] = ['MaKhoaLop' => $maKhoaLop, 'NamHoc' => $namHoc];
        }

        $updatedClasses = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($rows, $classKeys, &$updatedClasses): void {
            foreach ($classKeys as $pair) {
                $query = static::query()->where('MaKhoaLop', $pair['MaKhoaLop']);
                if ($pair['NamHoc'] === null) {
                    $query->whereNull('NamHoc');
                } else {
                    $query->where('NamHoc', $pair['NamHoc']);
                }

                if ($query->delete() > 0) {
                    $updatedClasses++;
                }
            }

            foreach (array_chunk($rows, self::insertBatchSize()) as $chunk) {
                static::query()->insert($chunk);
            }
        });

        $totalClasses = count($classKeys);

        return [
            'saved' => count($rows),
            'updated_classes' => $updatedClasses,
            'new_classes' => $totalClasses - $updatedClasses,
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return list<array<string, mixed>>
     */
    public static function rowsFromPreview(array $preview): array
    {
        $rows = [];

        foreach ($preview['sheets'] ?? [] as $sheet) {
            foreach ($sheet['records'] ?? [] as $record) {
                $maKhoaLop = trim((string) ($record['MaKhoaLop'] ?? ''));
                if ($maKhoaLop === '') {
                    continue;
                }

                $kyHieu = trim((string) ($record['KyHieu'] ?? ''));
                if ($kyHieu === '') {
                    continue;
                }

                $rows[] = [
                    'MaKhoaLop' => self::limit($maKhoaLop, 50),
                    'GiaoVienDay' => self::nullableLimit($record['GiaoVienDay'] ?? null, 100),
                    'SoLuongHocVien' => self::nullableInt($record['SoLuongHocVien'] ?? null),
                    'SoHocVienTotNghiep' => self::nullableInt($record['SoHocVienTotNghiep'] ?? null),
                    'NamHoc' => self::nullableInt($record['nam_hoc'] ?? null),
                    'ThangNam' => self::nullableLimit($record['ThangNam'] ?? null, 20),
                    'TuanThu' => self::nullableInt($record['TuanThu'] ?? null),
                    'TuNgay' => self::nullableDate($record['TuNgay'] ?? null),
                    'DenNgay' => self::nullableDate($record['DenNgay'] ?? null),
                    'KyHieu' => self::limit($kyHieu, 10),
                    'GhiChu' => self::nullableLimit($record['GhiChu'] ?? null, 500),
                ];
            }
        }

        return $rows;
    }

    private static function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function nullableLimit(mixed $value, int $maxLen): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return self::limit($text, $maxLen);
    }

    private static function limit(string $value, int $maxLen): string
    {
        if (mb_strlen($value) <= $maxLen) {
            return $value;
        }

        return mb_substr($value, 0, $maxLen);
    }

    private static function insertBatchSize(): int
    {
        $columnCount = count((new static)->getFillable());

        return max(1, intdiv(self::SQLSERVER_MAX_PARAMS, max(1, $columnCount)) - 1);
    }

    /** @var list<string> */
    public const HANG_GPLX = ['B', 'B01', 'C'];

    /** Hạng GPLX suy ra từ mã khóa-lớp: B01*, C*, còn lại B* → B. */
    public static function parseHangFromMaKhoaLop(string $maKhoaLop): ?string
    {
        $maKhoaLop = strtoupper(trim($maKhoaLop));
        if ($maKhoaLop === '') {
            return null;
        }

        if (str_starts_with($maKhoaLop, 'B01')) {
            return 'B01';
        }

        if (str_starts_with($maKhoaLop, 'C')) {
            return 'C';
        }

        if (str_starts_with($maKhoaLop, 'B')) {
            return 'B';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function hangOptions(): array
    {
        return self::HANG_GPLX;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function kyHieuFilterOptions(): array
    {
        return collect(self::kyHieuLegend())
            ->map(fn (array $item): array => [
                'value' => $item['token'],
                'label' => $item['token'].' — '.$item['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     */
    private static function applyKyHieuFilter($query, string $kyHieuFilter): void
    {
        $kyHieuFilter = self::normalizeKyHieuDisplay($kyHieuFilter);
        if ($kyHieuFilter === '') {
            return;
        }

        $tokens = array_column(self::parseKyHieuParts($kyHieuFilter), 'token');
        if ($tokens === []) {
            $query->where('KyHieu', 'like', '%'.$kyHieuFilter.'%');

            return;
        }

        foreach ($tokens as $token) {
            $query->where('KyHieu', 'like', '%'.$token.'%');
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     */
    private static function applyHangFilter($query, string $hangFilter): void
    {
        $hangFilter = strtoupper(trim($hangFilter));
        if ($hangFilter === '' || ! in_array($hangFilter, self::HANG_GPLX, true)) {
            return;
        }

        if ($hangFilter === 'B01') {
            $query->where('MaKhoaLop', 'like', 'B01%');

            return;
        }

        if ($hangFilter === 'C') {
            $query->where('MaKhoaLop', 'like', 'C%');

            return;
        }

        // B: bắt đầu B nhưng không phải B01
        $query->where('MaKhoaLop', 'like', 'B%')
            ->where('MaKhoaLop', 'not like', 'B01%');
    }

    /**
     * Báo cáo lưu lượng tại thời điểm: lớp có tuần (TuNgay–DenNgay) bao trùm ngày kiểm tra.
     *
     * @return array{
     *     items: list<array{hang: string, so_luong: int}>,
     *     classes: list<array<string, mixed>>,
     *     tong_so: int
     * }
     */
    public static function baoCaoLuuLuongAt(\Carbon\Carbon $at, string $hangFilter = '', string $kyHieuFilter = ''): array
    {
        $date = $at->toDateString();

        $query = static::query()
            ->whereNotNull('TuNgay')
            ->whereNotNull('DenNgay')
            ->whereDate('TuNgay', '<=', $date)
            ->whereDate('DenNgay', '>=', $date);

        if ($hangFilter !== '') {
            self::applyHangFilter($query, $hangFilter);
        }

        if ($kyHieuFilter !== '') {
            self::applyKyHieuFilter($query, $kyHieuFilter);
        }

        $rows = $query->get([
            'MaKhoaLop',
            'NamHoc',
            'SoLuongHocVien',
            'GiaoVienDay',
            'TuNgay',
            'DenNgay',
            'KyHieu',
            'ThangNam',
            'TuanThu',
        ]);

        $classes = $rows
            ->groupBy(fn ($row) => $row->MaKhoaLop.'|'.($row->NamHoc ?? 'null'))
            ->map(function ($group): ?array {
                /** @var static $weekRow */
                $weekRow = $group->sortByDesc('TuNgay')->first();
                $maKhoaLop = (string) $weekRow->MaKhoaLop;
                $hang = self::parseHangFromMaKhoaLop($maKhoaLop);
                if ($hang === null) {
                    return null;
                }

                $kyHieu = (string) ($weekRow->KyHieu ?? '');

                return [
                    'ma_khoa_lop' => $maKhoaLop,
                    'nam_hoc' => $weekRow->NamHoc,
                    'so_hoc_vien' => (int) ($group->max('SoLuongHocVien') ?? 0),
                    'giao_vien_day' => (string) ($weekRow->GiaoVienDay ?? ''),
                    'hang' => $hang,
                    'ky_hieu' => $kyHieu,
                    'ky_hieu_parts' => self::parseKyHieuParts($kyHieu),
                    'thang_nam' => (string) ($weekRow->ThangNam ?? ''),
                    'tuan_thu' => $weekRow->TuanThu,
                    'tuan_tu' => $weekRow->TuNgay?->format('d/m/Y') ?? '—',
                    'tuan_den' => $weekRow->DenNgay?->format('d/m/Y') ?? '—',
                    'giai_thich' => self::giaiThichKyHieu($kyHieu),
                ];
            })
            ->filter()
            ->values()
            ->sortBy('ma_khoa_lop')
            ->values();

        $byHang = $classes->groupBy('hang')->map(fn ($group) => (int) $group->sum('so_hoc_vien'));

        $items = collect(self::HANG_GPLX)
            ->map(fn (string $hang) => [
                'hang' => $hang,
                'so_luong' => (int) ($byHang->get($hang, 0)),
            ])
            ->when($hangFilter !== '', fn ($coll) => $coll->where('hang', strtoupper($hangFilter)))
            ->values()
            ->all();

        return [
            'items' => $items,
            'classes' => $classes->all(),
            'tong_so' => (int) $classes->sum('so_hoc_vien'),
        ];
    }

    public static function normalizeKyHieuDisplay(?string $kyHieu): string
    {
        $kyHieu = trim((string) ($kyHieu ?? ''));
        if ($kyHieu === '') {
            return '';
        }

        $kyHieu = preg_replace('/\s+/u', ' ', $kyHieu) ?? $kyHieu;
        $kyHieu = preg_replace('/\s*•\s*/u', ' •', $kyHieu) ?? $kyHieu;

        return trim($kyHieu);
    }

    /**
     * Tách ký hiệu ghép (HT, TĐ, ĐT • …) thành từng phần.
     *
     * @return list<array{token: string, label: string, css_class: string}>
     */
    public static function parseKyHieuParts(?string $kyHieu): array
    {
        $kyHieu = self::normalizeKyHieuDisplay($kyHieu);
        if ($kyHieu === '') {
            return [];
        }

        $map = [
            'H' => ['label' => 'Học lý thuyết', 'css_class' => 'ky-hieu-h'],
            'T' => ['label' => 'Tập lái trong hình', 'css_class' => 'ky-hieu-t'],
            'Đ' => ['label' => 'Tập lái trên đường', 'css_class' => 'ky-hieu-d'],
            '•' => ['label' => 'Kiểm tra', 'css_class' => 'ky-hieu-kiem'],
        ];

        $parts = [];
        $len = mb_strlen($kyHieu);
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($kyHieu, $i, 1);
            if ($ch === ' ') {
                continue;
            }

            if (isset($map[$ch])) {
                $parts[] = array_merge(['token' => $ch], $map[$ch]);
            }
        }

        return $parts;
    }

    public static function giaiThichKyHieu(?string $kyHieu): string
    {
        $parts = self::parseKyHieuParts($kyHieu);
        if ($parts === []) {
            $raw = self::normalizeKyHieuDisplay($kyHieu);

            return $raw !== '' ? $raw : '—';
        }

        return implode(' · ', array_column($parts, 'label'));
    }

    /**
     * @return list<array{token: string, label: string, css_class: string}>
     */
    public static function kyHieuLegend(): array
    {
        return [
            ['token' => 'H', 'label' => 'Học lý thuyết', 'css_class' => 'ky-hieu-h'],
            ['token' => 'T', 'label' => 'Tập lái trong hình', 'css_class' => 'ky-hieu-t'],
            ['token' => 'Đ', 'label' => 'Tập lái trên đường', 'css_class' => 'ky-hieu-d'],
            ['token' => '•', 'label' => 'Kiểm tra', 'css_class' => 'ky-hieu-kiem'],
        ];
    }
}
