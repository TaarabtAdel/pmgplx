<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaiDay extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoBaiDay';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    /** Mã hạng (dùng trong URL) => nhãn hiển thị. */
    public const HANG = [
        'B' => 'B',
        'B01' => 'B01',
        'C' => 'C',
    ];

    /** Mã ca (dùng trong URL) => nhãn hiển thị. */
    public const CA = [
        'sang' => 'Sáng',
        'chieu' => 'Chiều',
    ];

    protected $fillable = [
        'Hang',
        'Ca',
        'NgaySo',
        'SoGio',
        'BaiHocId',
        'ThuTu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgaySo' => 'integer',
        'SoGio' => 'float',
        'BaiHocId' => 'integer',
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    /** Nội dung + Giờ BĐ/KT của ngày lấy từ bài học. */
    public function baiHoc(): BelongsTo
    {
        return $this->belongsTo(BaiHoc::class, 'BaiHocId', 'Id');
    }

    /**
     * Giờ BĐ/KT lưu dạng chữ ("5h59", "13H59' -17H59'"). Trả số phút nếu ô chỉ có đúng một mốc giờ, ngược lại null.
     */
    public static function phutMotMoc(?string $text): ?int
    {
        preg_match_all('/(\d{1,2})\s*[hH:]\s*(\d{2})/u', (string) $text, $m, PREG_SET_ORDER);
        if (count($m) !== 1) {
            return null;
        }

        return (int) $m[0][1] * 60 + (int) $m[0][2];
    }
}
