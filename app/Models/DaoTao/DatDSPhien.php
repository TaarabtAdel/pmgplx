<?php

namespace App\Models\DaoTao;

use App\Support\DaoTao\DatDSPhienExcelParser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatDSPhien extends Model
{
    private const SQLSERVER_MAX_PARAMS = 2100;

    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatDSPhien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaPhienHoc',
        'TiLeNhanDien',
        'ThoiGianBatDauPhienHoc',
        'ThoiGianKetThucPhienHoc',
        'ThoiGianThucHanhGio',
        'QuangDuongThucHanhKm',
        'ThoiGianLaiBanDemGio',
        'ThoiGianLaiXeSoTuDong',
        'ThoiGianMayChuNhanPhienHoc',
        'MaHocVien',
        'HoTenHocVien',
        'MaKhoaHoc',
        'TenKhoaHoc',
        'LoaiKhoaHoc',
        'HoTenGiaoVien',
        'MaGiaoVien',
        'BienSoXe',
        'MaThietBi',
        'FileNguon',
        'NgayNhap',
    ];

    protected $casts = [
        'TiLeNhanDien' => 'float',
        'ThoiGianBatDauPhienHoc' => 'datetime',
        'ThoiGianKetThucPhienHoc' => 'datetime',
        'ThoiGianThucHanhGio' => 'float',
        'QuangDuongThucHanhKm' => 'float',
        'ThoiGianLaiBanDemGio' => 'float',
        'ThoiGianLaiXeSoTuDong' => 'float',
        'ThoiGianMayChuNhanPhienHoc' => 'datetime',
        'NgayNhap' => 'datetime',
    ];

    /**
     * Import trực tiếp từ file Excel (đọc theo lô, không giữ toàn bộ trong session).
     *
     * @return array{saved: int, updated: int, skipped: int}
     *
     * @throws Throwable
     */
    public static function upsertFromExcelFile(string $filePath, string $fileName): array
    {
        $parser = new DatDSPhienExcelParser();
        $rows = [];
        $skipped = 0;

        foreach ($parser->iterateRecordsFromFile($filePath) as $record) {
            $maPhien = trim((string) ($record['MaPhienHoc'] ?? ''));
            if ($maPhien === '') {
                $skipped++;

                continue;
            }

            $row = self::dbRowFromRecord($record, '', '');
            unset($row['FileNguon'], $row['NgayNhap']);
            $rows[] = $row;
        }

        return self::persistImportedRows($rows, $fileName, $skipped);
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array{saved: int, updated: int, skipped: int}
     *
     * @throws Throwable
     */
    public static function upsertFromPreview(array $preview): array
    {
        return self::persistImportedRows(
            self::rowsFromPreview($preview),
            (string) ($preview['file_name'] ?? ''),
            (int) ($preview['meta']['skipped_count'] ?? 0)
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{saved: int, updated: int, skipped: int}
     *
     * @throws Throwable
     */
    private static function persistImportedRows(array $rows, string $fileName, int $skipped = 0): array
    {
        if ($rows === []) {
            return ['saved' => 0, 'updated' => 0, 'skipped' => $skipped];
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $fileSource = self::limit($fileName, 255);
        $maPhienList = array_values(array_unique(array_column($rows, 'MaPhienHoc')));

        $existingLookup = array_fill_keys(
            static::query()->whereIn('MaPhienHoc', $maPhienList)->pluck('MaPhienHoc')->all(),
            true
        );

        $saved = 0;
        $updated = 0;
        $persistRows = [];

        foreach ($rows as $row) {
            $maPhien = (string) ($row['MaPhienHoc'] ?? '');
            $row['FileNguon'] = $fileSource;
            $row['NgayNhap'] = $now;

            if (isset($existingLookup[$maPhien])) {
                $updated++;
            } else {
                $saved++;
                $existingLookup[$maPhien] = true;
            }

            $persistRows[] = $row;
        }

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($persistRows): void {
            foreach (array_chunk($persistRows, self::insertBatchSize()) as $chunk) {
                static::query()->upsert($chunk, ['MaPhienHoc'], self::upsertUpdateColumns());
            }
        });

        return [
            'saved' => $saved,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return list<string>
     */
    private static function upsertUpdateColumns(): array
    {
        return array_values(array_filter(
            (new static)->getFillable(),
            fn (string $column): bool => $column !== 'MaPhienHoc'
        ));
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private static function dbRowFromRecord(array $record, string $fileSource, string $ngayNhap): array
    {
        $maPhien = trim((string) ($record['MaPhienHoc'] ?? ''));

        return [
            'MaPhienHoc' => self::limit($maPhien, 100),
            'TiLeNhanDien' => self::nullableFloat($record['TiLeNhanDien'] ?? null),
            'ThoiGianBatDauPhienHoc' => self::nullableDateTime($record['ThoiGianBatDauPhienHoc'] ?? null),
            'ThoiGianKetThucPhienHoc' => self::nullableDateTime($record['ThoiGianKetThucPhienHoc'] ?? null),
            'ThoiGianThucHanhGio' => self::nullableFloat($record['ThoiGianThucHanhGio'] ?? null),
            'QuangDuongThucHanhKm' => self::nullableFloat($record['QuangDuongThucHanhKm'] ?? null),
            'ThoiGianLaiBanDemGio' => self::nullableFloat($record['ThoiGianLaiBanDemGio'] ?? null),
            'ThoiGianLaiXeSoTuDong' => self::nullableFloat($record['ThoiGianLaiXeSoTuDong'] ?? null),
            'ThoiGianMayChuNhanPhienHoc' => self::nullableDateTime($record['ThoiGianMayChuNhanPhienHoc'] ?? null),
            'MaHocVien' => self::nullableLimit($record['MaHocVien'] ?? null, 50),
            'HoTenHocVien' => self::nullableLimit($record['HoTenHocVien'] ?? null, 255),
            'MaKhoaHoc' => self::nullableLimit($record['MaKhoaHoc'] ?? null, 50),
            'TenKhoaHoc' => self::nullableLimit($record['TenKhoaHoc'] ?? null, 255),
            'LoaiKhoaHoc' => self::nullableLimit($record['LoaiKhoaHoc'] ?? null, 100),
            'HoTenGiaoVien' => self::nullableLimit($record['HoTenGiaoVien'] ?? null, 255),
            'MaGiaoVien' => self::nullableLimit($record['MaGiaoVien'] ?? null, 50),
            'BienSoXe' => self::nullableLimit($record['BienSoXe'] ?? null, 50),
            'MaThietBi' => self::nullableLimit($record['MaThietBi'] ?? null, 100),
            'FileNguon' => $fileSource,
            'NgayNhap' => $ngayNhap,
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return list<array<string, mixed>>
     */
    public static function rowsFromPreview(array $preview): array
    {
        $rows = [];

        foreach ($preview['records'] ?? [] as $record) {
            $maPhien = trim((string) ($record['MaPhienHoc'] ?? ''));
            if ($maPhien === '') {
                continue;
            }

            $row = self::dbRowFromRecord($record, '', '');
            unset($row['FileNguon'], $row['NgayNhap']);
            $rows[] = $row;
        }

        return $rows;
    }

    private static function nullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
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
}
