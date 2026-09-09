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
        'SoGioHoc' => 'float',
        'TongQuangDuongKm' => 'float',
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];
}
