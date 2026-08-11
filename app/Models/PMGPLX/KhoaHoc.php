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

    public static $TIME_SLOTS = [
        ['start' => '05:58', 'end' => '13:58'],
        ['start' => '05:59', 'end' => '11:59'],
        ['start' => '05:59', 'end' => '13:59'],
        ['start' => '07:00', 'end' => '17:00'],
        ['start' => '12:00', 'end' => '18:00'],
        ['start' => '12:00', 'end' => '20:00'],
        ['start' => '13:59', 'end' => '17:59'],
        ['start' => '14:00', 'end' => '22:00'],
        ['start' => '18:00', 'end' => '22:00'],
        ['start' => '20:01', 'end' => '12:01'],
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
