<?php

namespace App\Models\DaoTao;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SDT và Sale cho học viên PMGPLX (MaDK), lưu trên MANHLINH.
 */
class HocVienThongTinPhu extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'HocVienThongTinPhu';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaDK',
        'SoDienThoai',
        'Sale',
        'NguoiCapNhatId',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function nguoiCapNhat(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiCapNhatId', 'Id');
    }
}
