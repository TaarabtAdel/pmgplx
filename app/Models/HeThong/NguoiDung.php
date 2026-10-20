<?php

namespace App\Models\HeThong;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class NguoiDung extends Authenticatable
{
    protected $connection = 'sqlsrv_manhlinh';

    /** Bảng không có cột remember token. */
    protected $rememberTokenName = '';

    protected $table = 'NguoiDung';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    public const VAI_TRO = [
        'admin' => 'Admin',
        'dao_tao' => 'Đào Tạo',
        'tuyen_sinh' => 'Tuyển Sinh',
        'it' => 'IT',
    ];

    protected $fillable = [
        'Ten',
        'SoDienThoai',
        'MatKhau',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $hidden = [
        'MatKhau',
    ];

    protected $casts = [
        'MatKhau' => 'hashed',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->MatKhau;
    }

    public function vaiTros(): HasMany
    {
        return $this->hasMany(NguoiDungVaiTro::class, 'NguoiDungId', 'Id');
    }

    /** @return list<string> */
    public function maVaiTroList(): array
    {
        $order = array_flip(array_keys(self::VAI_TRO));

        return $this->vaiTros
            ->pluck('MaVaiTro')
            ->map(fn ($v): string => (string) $v)
            ->sortBy(fn (string $ma): int => $order[$ma] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function tenVaiTroList(): array
    {
        return array_map(
            static fn (string $ma): string => self::VAI_TRO[$ma] ?? $ma,
            $this->maVaiTroList()
        );
    }

    public function coVaiTro(string $maVaiTro): bool
    {
        return in_array($maVaiTro, $this->maVaiTroList(), true);
    }

    /** @param  list<string>  $maVaiTros */
    public function ganVaiTro(array $maVaiTros): void
    {
        $maVaiTros = array_values(array_unique($maVaiTros));
        $this->vaiTros()->whereNotIn('MaVaiTro', $maVaiTros)->delete();
        $hienCo = $this->vaiTros()->pluck('MaVaiTro')->all();
        foreach (array_diff($maVaiTros, $hienCo) as $ma) {
            $this->vaiTros()->create(['MaVaiTro' => $ma]);
        }
        $this->unsetRelation('vaiTros');
    }
}
