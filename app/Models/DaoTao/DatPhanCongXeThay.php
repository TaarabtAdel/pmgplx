<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatPhanCongXeThay extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatPhanCongXeThay';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaHoc',
        'MaGiaoVienGoc',
        'BienSoXeGoc',
        'BienSoXe',
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
