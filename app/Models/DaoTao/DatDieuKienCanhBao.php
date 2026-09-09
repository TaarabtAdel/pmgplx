<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatDieuKienCanhBao extends Model
{
    public const DEFAULT_MIN_PHUT = 5;

    public const DEFAULT_MAX_PHUT = 240;

    public const DEFAULT_KHOANG_PHUT = 15;

    public const DEFAULT_TI_LE = 75.0;

    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatDieuKienCanhBao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'ThoiGianPhienToiThieuPhut',
        'ThoiGianPhienToiDaPhut',
        'KhoangPhienLienKePhut',
        'TiLeNhanDienToiThieu',
        'NgayCapNhat',
    ];

    protected $casts = [
        'ThoiGianPhienToiThieuPhut' => 'integer',
        'ThoiGianPhienToiDaPhut' => 'integer',
        'KhoangPhienLienKePhut' => 'integer',
        'TiLeNhanDienToiThieu' => 'float',
        'NgayCapNhat' => 'datetime',
    ];

    private static ?self $cached = null;

    public static function hienTai(): self
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $row = static::query()->orderBy('Id')->first();

        if ($row === null) {
            $row = static::query()->create([
                'ThoiGianPhienToiThieuPhut' => self::DEFAULT_MIN_PHUT,
                'ThoiGianPhienToiDaPhut' => self::DEFAULT_MAX_PHUT,
                'KhoangPhienLienKePhut' => self::DEFAULT_KHOANG_PHUT,
                'TiLeNhanDienToiThieu' => self::DEFAULT_TI_LE,
                'NgayCapNhat' => now(),
            ]);
        }

        self::$cached = $row;

        return $row;
    }

    public static function resetCache(): void
    {
        self::$cached = null;
    }

    /**
     * @return array{
     *     min_phut: int,
     *     max_phut: int,
     *     khoang_phut: int,
     *     ti_le: float
     * }
     */
    public function toSettingsArray(): array
    {
        return [
            'min_phut' => (int) ($this->ThoiGianPhienToiThieuPhut ?? self::DEFAULT_MIN_PHUT),
            'max_phut' => (int) ($this->ThoiGianPhienToiDaPhut ?? self::DEFAULT_MAX_PHUT),
            'khoang_phut' => (int) ($this->KhoangPhienLienKePhut ?? self::DEFAULT_KHOANG_PHUT),
            'ti_le' => (float) ($this->TiLeNhanDienToiThieu ?? self::DEFAULT_TI_LE),
        ];
    }
}
