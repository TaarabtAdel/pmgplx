<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatPhanCongGiaoVienThay extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatPhanCongGiaoVienThay';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaHoc',
        'MaGiaoVienGoc',
        'MaGiaoVien',
        'TuNgay',
        'DenNgay',
        'TuGio',
        'DenGio',
        'NgayNhap',
    ];

    /** 'H:i' hoặc null. */
    public static function gioHienThi(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^(\d{1,2}):(\d{2})/', $value, $m) === 1
            ? str_pad($m[1], 2, '0', STR_PAD_LEFT).':'.$m[2]
            : null;
    }

    protected $casts = [
        'TuNgay' => 'date',
        'DenNgay' => 'date',
        'NgayNhap' => 'datetime',
    ];
}
