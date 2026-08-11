<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;

class KhoaHoc extends Model
{
    protected $table = 'KhoaHoc';

    protected $primaryKey = 'MaKH';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public static $DIA_DIEM = [
        'Phòng học lý thuyết',
        'Sân tập lái và sân sát hạch',
        'Các tuyến đường giao thông'
    ];

    public static $THANG_THI = [
        '01' => 'Tháng 1',
        '02' => 'Tháng 2',
        '03' => 'Tháng 3',
        '04' => 'Tháng 4',
        '05' => 'Tháng 5',
        '06' => 'Tháng 6',
        '07' => 'Tháng 7',
        '08' => 'Tháng 8',
        '09' => 'Tháng 9',
        '10' => 'Tháng 10',
        '11' => 'Tháng 11',
        '12' => 'Tháng 12',
    ];

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayKG' => 'datetime',
        'NgayBG' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('TrangThai', 1);
    }
}
