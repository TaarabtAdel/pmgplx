<?php

namespace App\Models\QldtOto;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một lần xuất file trong QLĐT Ô TÔ. */
class LichSuXuatFile extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichSuXuatFile';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'BieuMauId',
        'TenRoute',
        'TenChucNang',
        'DuongDan',
        'PhuongThuc',
        'TenFile',
        'LoaiFile',
        'SoCongVan',
        'NgayVanBan',
        'MaKh',
        'ThamSoJson',
        'NguoiXuatId',
        'ThoiGianXuat',
        'DiaChiIp',
    ];

    protected $casts = [
        'ThoiGianXuat' => 'datetime',
        'NgayVanBan' => 'date',
    ];

    public function bieuMau(): BelongsTo
    {
        return $this->belongsTo(BieuMauXuat::class, 'BieuMauId', 'Id');
    }

    public function nguoiXuat(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiXuatId', 'Id');
    }
}