<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;

class KhoaHocXeTap extends Model
{
    protected $table = 'KhoaHoc_XeTap';

    protected $primaryKey = 'MaLichSD';

    public $timestamps = false;

    protected $fillable = [
        'MaKH',
        'BienSoXe',
        'MaGV',
        'MaHV',
        'DiaDiem',
        'GhiChu',
        'TrangThai',
        'NguoiTao',
        'NguoiSua',
        'NgayTao',
        'NgaySua',
        'NgayBD',
        'NgayKT',
        'IsKhoaHocXeTap',
        'TenHV',
        'TenGV',
    ];

    protected $casts = [
        'TrangThai' => 'boolean',
        'IsKhoaHocXeTap' => 'boolean',
        'NgayBD' => 'datetime',
        'NgayKT' => 'datetime',
        'NgayTao' => 'datetime',
        'NgaySua' => 'datetime',
    ];
}
