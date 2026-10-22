<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Bài học của một hạng + ca (sáng/chiều riêng): tên + khung giờ. Các ngày trong cấu hình bài dạy (BaiDay) gán tới bài học. */
class BaiHoc extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoBaiHoc';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'Hang',
        'Ca',
        'Ten',
        'GioBatDau',
        'GioKetThuc',
        'ThuTu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function baiDays(): HasMany
    {
        return $this->hasMany(BaiDay::class, 'BaiHocId', 'Id');
    }

    /** Gộp khoảng trắng thừa ("Tự động  (S)" → "Tự động (S)"). */
    public static function chuanHoaTen(?string $ten): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $ten));
    }
}
