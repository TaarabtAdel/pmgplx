<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatPhanCongHocVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatPhanCongHocVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaHoc',
        'MaHocVien',
        'HoTenHocVien',
        'MaGiaoVien',
        'BienSoXe',
        'BienSoXeTuDong',
        'FileNguon',
        'NgayNhap',
        'TuNgay',
        'DenNgay',
    ];

    protected $casts = [
        'NgayNhap' => 'datetime',
        'TuNgay' => 'date',
        'DenNgay' => 'date',
    ];

    /** Ngày (Y-m-d) nằm trong khoảng phân công; TuNgay/DenNgay NULL = không giới hạn. */
    public function apDungNgay(?string $ngay): bool
    {
        if ($ngay === null || $ngay === '') {
            return true;
        }
        if ($this->TuNgay !== null && $ngay < $this->TuNgay->toDateString()) {
            return false;
        }

        return $this->DenNgay === null || $ngay <= $this->DenNgay->toDateString();
    }

    public function nhanKhoangNgay(): string
    {
        if ($this->TuNgay === null && $this->DenNgay === null) {
            return '';
        }

        return 'từ '.($this->TuNgay?->format('d/m/Y') ?? 'đầu khóa')
            .' đến '.($this->DenNgay?->format('d/m/Y') ?? 'hết khóa');
    }
}
