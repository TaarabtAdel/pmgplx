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
        'NgayNhap',
    ];

    protected $casts = [
        'TuNgay' => 'date',
        'DenNgay' => 'date',
        'NgayNhap' => 'datetime',
    ];
}
