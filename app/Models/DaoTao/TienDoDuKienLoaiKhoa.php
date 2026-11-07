<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một chuỗi khoá dự kiến nối tiếp nhau. NgayKhaiGiang = NULL → nối tiếp khoá thực tế tốt nghiệp muộn nhất cùng hạng.
 */
class TienDoDuKienLoaiKhoa extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'TienDoDuKienLoaiKhoa';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'TenLoai',
        'Hang',
        'SoHocVienToiDa',
        'SoNgayLyThuyet',
        'SoNgayThucHanh',
        'SoHocVienMoiXe',
        'SoGiaoVienMoiXe',
        'SoNgayCach',
        'NgayKhaiGiang',
        'DangSuDung',
        'ThuTu',
        'MauSac',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'SoHocVienToiDa' => 'integer',
        'SoNgayLyThuyet' => 'integer',
        'SoNgayThucHanh' => 'integer',
        'SoHocVienMoiXe' => 'integer',
        'SoGiaoVienMoiXe' => 'integer',
        'SoNgayCach' => 'integer',
        'NgayKhaiGiang' => 'date',
        'DangSuDung' => 'boolean',
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function tangXe(): HasMany
    {
        return $this->hasMany(TienDoDuKienTangXe::class, 'LoaiKhoaId')->orderBy('TuNgay');
    }

    /** Số GV cơ bản = SL tối đa ÷ HV (mỗi GV). */
    public function soGvCoBan(): int
    {
        return (int) ceil($this->SoHocVienToiDa / max(1, $this->SoHocVienMoiXe));
    }

    /** @deprecated Dùng soGvCoBan() — giữ tên cũ cho tương thích. */
    public function soXeCoBan(): int
    {
        return $this->soGvCoBan();
    }
}
