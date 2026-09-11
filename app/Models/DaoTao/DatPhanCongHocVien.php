<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatPhanCongHocVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatPhanCongHocVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaHoc',
        'MaHocVien',
        'HoTenHocVien',
        'MaGiaoVien',
        'BienSoXe',
        'BienSoXeTuDong',
        'FileNguon',
        'NgayNhap',
    ];

    protected $casts = [
        'NgayNhap' => 'datetime',
    ];
}
