<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;

/** Một buổi trong lịch dự kiến đã sinh: cặp GV, ca, ngày + bài dạy (chụp lại lúc sinh lịch). */
class LichDuKienThucHanhBuoi extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichDuKienThucHanhBuoi';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichDuKienId',
        'CapThuTu',
        'Ca',
        'MaGv',
        'BienSoXe',
        'Ngay',
        'NgaySo',
        'NoiDung',
        'GioBatDau',
        'GioKetThuc',
        'SoGio',
    ];

    protected $casts = [
        'CapThuTu' => 'integer',
        'Ngay' => 'date',
        'NgaySo' => 'integer',
        'SoGio' => 'float',
    ];
}
