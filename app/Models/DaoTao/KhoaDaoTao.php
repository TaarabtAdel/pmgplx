<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KhoaDaoTao extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'KhoaDaoTao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoa',
        'TenKhoa',
        'HangDaoTao',
        'NgayKhaiGiang',
        'NgayBeGiang',
        'TrangThai',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayKhaiGiang' => 'date',
        'NgayBeGiang' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function phanCong(): HasMany
    {
        return $this->hasMany(PhanCongDaoTao::class, 'KhoaDaoTaoId');
    }

    public static function findOrCreateByTenKhoa(string $tenKhoa): self
    {
        $tenKhoa = self::normalizeTenKhoa($tenKhoa);

        return static::query()->firstOrCreate(
            ['TenKhoa' => $tenKhoa],
            [
                'HangDaoTao' => self::guessHangDaoTao($tenKhoa),
                'TrangThai' => 'Đang đào tạo',
                'NgayTao' => now(),
            ]
        );
    }

    public static function normalizeTenKhoa(string $tenKhoa): string
    {
        return strtoupper(trim($tenKhoa));
    }

    private static function guessHangDaoTao(string $tenKhoa): ?string
    {
        if (preg_match('/^([A-Z]\d?)/', $tenKhoa, $m)) {
            return $m[1];
        }

        return null;
    }
}
