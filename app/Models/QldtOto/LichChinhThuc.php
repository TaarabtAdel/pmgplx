<?php

namespace App\Models\QldtOto;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Lịch chính thức thực hành — sao từ lịch dự kiến, chỉnh thứ tự bài rồi lưu PMGPLX. */
class LichChinhThuc extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichChinhThuc';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichDuKienId',
        'TenKhoa',
        'MaKH',
        'Hang',
        'NgayBatDauThucHanh',
        'XeTuDong',
        'CabinCapTruocNgay',
        'NguoiTaoId',
        'NgayTao',
        'NgayCapNhat',
        'NgayLuuPm',
        'SoBuoiLuuPm',
    ];

    protected $casts = [
        'NgayBatDauThucHanh' => 'date',
        'CabinCapTruocNgay' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
        'NgayLuuPm' => 'datetime',
        'SoBuoiLuuPm' => 'integer',
    ];

    public function ngayNghis(): HasMany
    {
        return $this->hasMany(LichChinhThucNgayNghi::class, 'LichChinhThucId', 'Id');
    }

    public function capGvs(): HasMany
    {
        return $this->hasMany(LichChinhThucCapGv::class, 'LichChinhThucId', 'Id');
    }

    public function buois(): HasMany
    {
        return $this->hasMany(LichChinhThucBuoi::class, 'LichChinhThucId', 'Id');
    }

    public function lichDuKien(): BelongsTo
    {
        return $this->belongsTo(LichDuKienThucHanh::class, 'LichDuKienId', 'Id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiTaoId', 'Id');
    }
}
