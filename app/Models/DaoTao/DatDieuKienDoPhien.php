<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class DatDieuKienDoPhien extends Model
{
    public const DEFAULT_SOM_PHUT = 0;

    public const DEFAULT_MUON_PHUT = 0;

    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatDieuKienDoPhien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'ChoPhepSomPhut',
        'ChoPhepMuonPhut',
        'NgayCapNhat',
    ];

    protected $casts = [
        'ChoPhepSomPhut' => 'integer',
        'ChoPhepMuonPhut' => 'integer',
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
                'ChoPhepSomPhut' => self::DEFAULT_SOM_PHUT,
                'ChoPhepMuonPhut' => self::DEFAULT_MUON_PHUT,
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
     * @return array{som_phut: int, muon_phut: int}
     */
    public function toSettingsArray(): array
    {
        return [
            'som_phut' => (int) ($this->ChoPhepSomPhut ?? self::DEFAULT_SOM_PHUT),
            'muon_phut' => (int) ($this->ChoPhepMuonPhut ?? self::DEFAULT_MUON_PHUT),
        ];
    }
}
