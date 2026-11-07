<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SoXe lưu ±GV (đơn vị năng lực); áp dụng cho khoá dự kiến khai giảng từ TuNgay. */
class TienDoDuKienTangXe extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'TienDoDuKienTangXe';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LoaiKhoaId',
        'TuNgay',
        'SoXe',
        'GhiChu',
        'NgayTao',
    ];

    protected $casts = [
        'TuNgay' => 'date',
        'SoXe' => 'integer',
        'NgayTao' => 'datetime',
    ];

    public function loaiKhoa(): BelongsTo
    {
        return $this->belongsTo(TienDoDuKienLoaiKhoa::class, 'LoaiKhoaId');
    }
}
