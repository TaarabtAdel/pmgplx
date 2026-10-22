<?php

namespace App\Models\QldtOto;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Lịch dự kiến thực hành của một khoá: hạng, ngày bắt đầu TH, xe tự động, cabin của cặp trước, ngày nghỉ, cặp GV sáng/chiều. */
class LichDuKienThucHanh extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichDuKienThucHanh';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'TenKhoa',
        'Hang',
        'NgayBatDauThucHanh',
        'XeTuDong',
        'CabinCapTruocNgay',
        'NguoiTaoId',
        'NgaySinhLich',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayBatDauThucHanh' => 'date',
        'CabinCapTruocNgay' => 'date',
        'NgaySinhLich' => 'datetime',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function ngayNghis(): HasMany
    {
        return $this->hasMany(LichDuKienThucHanhNgayNghi::class, 'LichDuKienId', 'Id');
    }

    public function capGvs(): HasMany
    {
        return $this->hasMany(LichDuKienThucHanhCapGv::class, 'LichDuKienId', 'Id');
    }

    public function buois(): HasMany
    {
        return $this->hasMany(LichDuKienThucHanhBuoi::class, 'LichDuKienId', 'Id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiTaoId', 'Id');
    }
}
