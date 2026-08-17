<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XeTapLai extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'XeTapLai';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'BienSo',
        'LoaiXe',
        'HangXe',
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
        return $this->hasMany(PhanCongDaoTao::class, 'XeTapLaiId');
    }

    public static function findOrCreateByBienSo(string $bienSo, ?string $loaiXe = null): self
    {
        $bienSo = self::normalizeBienSo($bienSo);

        $xe = static::query()->where('BienSo', $bienSo)->first();
        if ($xe !== null) {
            if ($loaiXe !== null && $xe->LoaiXe === null) {
                $xe->update(['LoaiXe' => $loaiXe, 'NgayCapNhat' => now()]);
            }

            return $xe;
        }

        return static::query()->create([
            'BienSo' => $bienSo,
            'LoaiXe' => $loaiXe,
            'TrangThai' => true,
            'NgayTao' => now(),
        ]);
    }

    public static function normalizeBienSo(string $bienSo): string
    {
        return preg_replace('/\s+/u', '', trim($bienSo)) ?? trim($bienSo);
    }
}
