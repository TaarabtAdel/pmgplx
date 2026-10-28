<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dữ liệu cột báo cáo GPLX không có (hoặc chưa có) trên PMGPLX — lưu MANHLINH. */
class CapNhatGPLXHocVienBoSung extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'CapNhatGPLXHocVienBoSung';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'CapNhatGPLXHocVienId',
        'MaDK',
        'NoiDKHoKhauThuongTru',
        'DaCoGPLX',
        'NgayTrungTuyen',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayTrungTuyen' => 'date',
        'NgayCapNhat' => 'datetime',
    ];

    public function capNhatGplx(): BelongsTo
    {
        return $this->belongsTo(CapNhatGPLXHocVien::class, 'CapNhatGPLXHocVienId', 'Id');
    }
}
