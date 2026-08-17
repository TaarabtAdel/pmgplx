<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Throwable;

class PhanCongDaoTao extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'PhanCongDaoTao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'SoTT',
        'GiaoVienId',
        'XeTapLaiId',
        'KhoaDaoTaoId',
        'TuNgay',
        'DenNgay',
        'NoiDungGiangDay',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'SoTT' => 'integer',
        'TuNgay' => 'date',
        'DenNgay' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function giaoVien(): BelongsTo
    {
        return $this->belongsTo(GiaoVien::class, 'GiaoVienId');
    }

    public function xeTapLai(): BelongsTo
    {
        return $this->belongsTo(XeTapLai::class, 'XeTapLaiId');
    }

    public function khoaDaoTao(): BelongsTo
    {
        return $this->belongsTo(KhoaDaoTao::class, 'KhoaDaoTaoId');
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array{saved: int, khoa_count: int, gv_created: int, xe_created: int, errors: list<string>}
     *
     * @throws Throwable
     */
    public static function importFromPreview(array $preview): array
    {
        $records = $preview['records'] ?? [];
        $errors = self::collectRowErrors($records);
        if ($errors !== []) {
            return ['saved' => 0, 'khoa_count' => 0, 'gv_created' => 0, 'xe_created' => 0, 'errors' => $errors];
        }

        $overlapErrors = self::validateOverlaps($records);
        if ($overlapErrors !== []) {
            return ['saved' => 0, 'khoa_count' => 0, 'gv_created' => 0, 'xe_created' => 0, 'errors' => $overlapErrors];
        }

        $saved = 0;
        $gvCreated = 0;
        $xeCreated = 0;
        $khoaIds = [];

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($records, &$saved, &$gvCreated, &$xeCreated, &$khoaIds): void {
            $khoaMap = [];
            foreach ($records as $record) {
                $tenKhoa = KhoaDaoTao::normalizeTenKhoa((string) ($record['TenKhoa'] ?? ''));
                if (! isset($khoaMap[$tenKhoa])) {
                    $existing = KhoaDaoTao::query()->where('TenKhoa', $tenKhoa)->exists();
                    $khoa = KhoaDaoTao::findOrCreateByTenKhoa($tenKhoa);
                    $khoaMap[$tenKhoa] = $khoa->Id;
                    $khoaIds[] = $khoa->Id;
                }
            }

            static::query()->whereIn('KhoaDaoTaoId', array_values(array_unique($khoaIds)))->delete();

            foreach ($records as $record) {
                $tenKhoa = KhoaDaoTao::normalizeTenKhoa((string) $record['TenKhoa']);
                $khoaId = $khoaMap[$tenKhoa];

                $giaoVienId = null;
                $hoTen = trim((string) ($record['HoTen'] ?? ''));
                if ($hoTen !== '') {
                    $before = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->exists();
                    $gv = GiaoVien::findOrCreateByHoTen($hoTen, self::guessLoaiGv((string) ($record['NoiDungGiangDay'] ?? '')));
                    if ($gv !== null) {
                        $giaoVienId = $gv->Id;
                        if (! $before) {
                            $gvCreated++;
                        }
                    }
                }

                $xeTapLaiId = null;
                $bienSo = trim((string) ($record['BienSo'] ?? ''));
                if ($bienSo !== '') {
                    $normalized = XeTapLai::normalizeBienSo($bienSo);
                    $before = XeTapLai::query()->where('BienSo', $normalized)->exists();
                    $xe = XeTapLai::findOrCreateByBienSo($bienSo, self::guessLoaiXe((string) ($record['NoiDungGiangDay'] ?? '')));
                    $xeTapLaiId = $xe->Id;
                    if (! $before) {
                        $xeCreated++;
                    }
                }

                static::query()->create([
                    'SoTT' => $record['SoTT'] ?? null,
                    'GiaoVienId' => $giaoVienId,
                    'XeTapLaiId' => $xeTapLaiId,
                    'KhoaDaoTaoId' => $khoaId,
                    'TuNgay' => $record['TuNgay'],
                    'DenNgay' => $record['DenNgay'],
                    'NoiDungGiangDay' => self::nullableLimit($record['NoiDungGiangDay'] ?? null, 100),
                    'GhiChu' => self::nullableLimit($record['GhiChu'] ?? null, 255),
                    'NgayTao' => now(),
                ]);
                $saved++;
            }
        });

        return [
            'saved' => $saved,
            'khoa_count' => count(array_unique($khoaIds)),
            'gv_created' => $gvCreated,
            'xe_created' => $xeCreated,
            'errors' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<string>
     */
    public static function collectRowErrors(array $records): array
    {
        $errors = [];
        foreach ($records as $i => $record) {
            $line = (int) ($record['excel_row'] ?? ($i + 2));
            $tenKhoa = trim((string) ($record['TenKhoa'] ?? ''));
            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            $bienSo = trim((string) ($record['BienSo'] ?? ''));

            if ($tenKhoa === '') {
                $errors[] = "Dòng {$line}: thiếu khoá đào tạo.";
                continue;
            }
            if (empty($record['TuNgay']) || empty($record['DenNgay'])) {
                $errors[] = "Dòng {$line}: không parse được thời gian.";
                continue;
            }
            if ($hoTen === '' && $bienSo === '') {
                $errors[] = "Dòng {$line}: cần có giáo viên hoặc biển số xe.";
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<string>
     */
    public static function validateOverlaps(array $records): array
    {
        $errors = [];
        $items = [];

        foreach ($records as $i => $record) {
            $items[] = [
                'index' => $i,
                'line' => (int) ($record['excel_row'] ?? ($i + 2)),
                'label' => self::recordLabel($record),
                'tu' => (string) $record['TuNgay'],
                'den' => (string) $record['DenNgay'],
                'gv_key' => self::gvKey($record),
                'xe_key' => self::xeKey($record),
                'ten_khoa' => KhoaDaoTao::normalizeTenKhoa((string) ($record['TenKhoa'] ?? '')),
            ];
        }

        for ($a = 0; $a < count($items); $a++) {
            for ($b = $a + 1; $b < count($items); $b++) {
                if (! self::rangesOverlap($items[$a]['tu'], $items[$a]['den'], $items[$b]['tu'], $items[$b]['den'])) {
                    continue;
                }
                if ($items[$a]['gv_key'] !== null && $items[$a]['gv_key'] === $items[$b]['gv_key']) {
                    $errors[] = "Dòng {$items[$a]['line']} và {$items[$b]['line']}: giáo viên trùng khoảng thời gian ({$items[$a]['label']}).";
                }
                if ($items[$a]['xe_key'] !== null && $items[$a]['xe_key'] === $items[$b]['xe_key']) {
                    $errors[] = "Dòng {$items[$a]['line']} và {$items[$b]['line']}: xe trùng khoảng thời gian ({$items[$a]['label']}).";
                }
            }
        }

        $khoaNames = array_values(array_unique(array_column($items, 'ten_khoa')));
        $khoaIds = KhoaDaoTao::query()->whereIn('TenKhoa', $khoaNames)->pluck('Id');

        $gvIdsInFile = [];
        $xeIdsInFile = [];
        foreach ($records as $record) {
            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            if ($hoTen !== '') {
                $gv = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->first();
                if ($gv !== null) {
                    $gvIdsInFile[] = $gv->Id;
                }
            }
            $bienSo = trim((string) ($record['BienSo'] ?? ''));
            if ($bienSo !== '') {
                $xe = XeTapLai::query()->where('BienSo', XeTapLai::normalizeBienSo($bienSo))->first();
                if ($xe !== null) {
                    $xeIdsInFile[] = $xe->Id;
                }
            }
        }

        if ($gvIdsInFile === [] && $xeIdsInFile === []) {
            return array_values(array_unique($errors));
        }

        $existingQuery = static::query()
            ->with(['giaoVien', 'xeTapLai', 'khoaDaoTao'])
            ->whereNotIn('KhoaDaoTaoId', $khoaIds->all());

        $existingQuery->where(function ($query) use ($gvIdsInFile, $xeIdsInFile): void {
            if ($gvIdsInFile !== []) {
                $query->whereIn('GiaoVienId', $gvIdsInFile);
            }
            if ($xeIdsInFile !== []) {
                $method = $gvIdsInFile !== [] ? 'orWhereIn' : 'whereIn';
                $query->{$method}('XeTapLaiId', $xeIdsInFile);
            }
        });

        $existing = $existingQuery->get();

        foreach ($items as $item) {
            $record = $records[$item['index']];
            $gvId = null;
            $xeId = null;
            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            if ($hoTen !== '') {
                $gvId = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->value('Id');
            }
            $bienSo = trim((string) ($record['BienSo'] ?? ''));
            if ($bienSo !== '') {
                $xeId = XeTapLai::query()->where('BienSo', XeTapLai::normalizeBienSo($bienSo))->value('Id');
            }

            foreach ($existing as $row) {
                if (! self::rangesOverlap($item['tu'], $item['den'], $row->TuNgay->toDateString(), $row->DenNgay->toDateString())) {
                    continue;
                }
                if ($gvId !== null && (int) $row->GiaoVienId === (int) $gvId) {
                    $errors[] = "Dòng {$item['line']}: giáo viên {$item['label']} trùng lịch với khoá {$row->khoaDaoTao?->TenKhoa} ({$row->TuNgay->format('d/m/Y')} – {$row->DenNgay->format('d/m/Y')}).";
                }
                if ($xeId !== null && (int) $row->XeTapLaiId === (int) $xeId) {
                    $errors[] = "Dòng {$item['line']}: xe trùng lịch với khoá {$row->khoaDaoTao?->TenKhoa} ({$row->TuNgay->format('d/m/Y')} – {$row->DenNgay->format('d/m/Y')}).";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function recordLabel(array $record): string
    {
        $parts = array_filter([
            trim((string) ($record['HoTen'] ?? '')),
            trim((string) ($record['BienSo'] ?? '')),
            trim((string) ($record['TenKhoa'] ?? '')),
        ]);

        return implode(' / ', $parts);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function gvKey(array $record): ?string
    {
        $hoTen = trim((string) ($record['HoTen'] ?? ''));
        if ($hoTen === '') {
            return null;
        }

        return mb_strtolower(GiaoVien::normalizeHoTen($hoTen));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function xeKey(array $record): ?string
    {
        $bienSo = trim((string) ($record['BienSo'] ?? ''));
        if ($bienSo === '') {
            return null;
        }

        return mb_strtolower(XeTapLai::normalizeBienSo($bienSo));
    }

    private static function rangesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart <= $bEnd && $aEnd >= $bStart;
    }

    private static function guessLoaiGv(string $noiDung): ?string
    {
        $upper = mb_strtoupper($noiDung);
        if (str_contains($upper, 'GVLT')) {
            return 'GVLT';
        }
        if (str_contains($upper, 'GVTH')) {
            return 'GVTH';
        }

        return null;
    }

    private static function guessLoaiXe(string $noiDung): ?string
    {
        if (mb_stripos($noiDung, 'tự động') !== false || mb_stripos($noiDung, 'tu dong') !== false) {
            return 'Số tự động';
        }

        return null;
    }

    private static function nullableLimit(mixed $value, int $maxLen): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return mb_strlen($text) <= $maxLen ? $text : mb_substr($text, 0, $maxLen);
    }
}
