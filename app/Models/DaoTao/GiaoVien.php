<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiaoVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'GiaoVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaGV',
        'HoTen',
        'LoaiGV',
        'SoDienThoai',
        'TrangThai',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function phanCong(): HasMany
    {
        return $this->hasMany(PhanCongDaoTao::class, 'GiaoVienId');
    }

    public static function findOrCreateByHoTen(string $hoTen, ?string $loaiGv = null): ?self
    {
        $hoTen = self::normalizeHoTen($hoTen);
        if ($hoTen === '') {
            return null;
        }

        $gv = static::query()->where('HoTen', $hoTen)->first();
        if ($gv !== null) {
            if ($loaiGv !== null && $gv->LoaiGV === null) {
                $gv->update(['LoaiGV' => $loaiGv, 'NgayCapNhat' => now()]);
            }

            return $gv;
        }

        return static::query()->create([
            'HoTen' => $hoTen,
            'LoaiGV' => $loaiGv,
            'TrangThai' => true,
            'NgayTao' => now(),
        ]);
    }

    public static function normalizeHoTen(string $hoTen): string
    {
        $hoTen = preg_replace('/\s+/u', ' ', trim($hoTen)) ?? trim($hoTen);

        return $hoTen;
    }
}
