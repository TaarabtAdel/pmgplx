<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;

class KhoaHocGiaoVien extends Model
{
    protected $table = 'KhoaHoc_GiaoVien';

    protected $primaryKey = 'MaLichLV';

    public $timestamps = false;

    protected $fillable = [
        'MaKH',
        'MaGV',
        'TenGV',
        'BienSoXe',
        'LoaiGV',
        'SoHV',
        'NgayHL',
        'NgayHetHL',
        'GhiChu',
        'TrangThai',
        'NguoiTao',
        'NguoiSua',
        'NgayTao',
        'NgaySua',
        'NgayBD',
        'NgayKT',
        'IsKhoaHocGiaoVien',
        'MaMonHoc',
        'TenMonHoc',
    ];

    protected $casts = [
        'TrangThai' => 'boolean',
        'IsKhoaHocGiaoVien' => 'boolean',
        'NgayBD' => 'datetime',
        'NgayKT' => 'datetime',
        'NgayTao' => 'datetime',
        'NgaySua' => 'datetime',
    ];
}
