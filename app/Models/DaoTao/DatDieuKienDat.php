<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatDieuKienDat extends Model
{
    public const AP_DUNG_TU_NGAY = '2025-09-01';

    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatDieuKienDat';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'Hang',
        'ApDungTuNgay',
        'TapLaiBanDemGio',
        'XeSoTuDongGio',
        'GioCaoTocGio',
        'SoGioHoc',
        'TongQuangDuongKm',
        'ThuTu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'ApDungTuNgay' => 'date',
        'TapLaiBanDemGio' => 'float',
        'XeSoTuDongGio' => 'float',
        'GioCaoTocGio' => 'float',
        'SoGioHoc' => 'float',
        'TongQuangDuongKm' => 'float',
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public static function normalizeHang(string $hang): string
    {
        return str_replace(['.', ' ', '-', '_'], '', mb_strtoupper(trim($hang)));
    }

    public static function hangFromCourse(string $maKhoaHoc, string $tenKhoaHoc = '', string $loaiKhoaHoc = ''): string
    {
        $loai = trim($loaiKhoaHoc);
        if ($loai !== '') {
            return $loai;
        }

        $haystack = self::normalizeHang($maKhoaHoc.' '.$tenKhoaHoc);
        if (str_contains($haystack, 'B01')) {
            return 'B.01';
        }
        if (str_contains($haystack, 'C1')) {
            return 'C1';
        }
        if (str_contains($haystack, 'B')) {
            return 'B';
        }

        return '';
    }

    public static function forHang(string $hang): ?self
    {
        $target = self::normalizeHang($hang);
        if ($target === '') {
            return null;
        }

        return static::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->first(fn (self $row): bool => self::normalizeHang((string) $row->Hang) === $target);
    }

    /**
     * @return array{bg: string, fg: string, excel: string}
     */
    public static function headerTheme(string $hang): array
    {
        $key = self::normalizeHang($hang);
        if (str_contains($key, 'B01')) {
            return ['bg' => '#2e7d32', 'fg' => '#ffffff', 'excel' => 'FF2E7D32'];
        }
        if (str_contains($key, 'C1')) {
            return ['bg' => '#ef6c00', 'fg' => '#ffffff', 'excel' => 'FFEF6C00'];
        }

        return ['bg' => '#1565c0', 'fg' => '#ffffff', 'excel' => 'FF1565C0'];
    }

    public static function datNguong(float $value, mixed $min): bool
    {
        $min = (float) $min;

        return $min > 0 && ($value + 0.0001) >= $min;
    }
}
