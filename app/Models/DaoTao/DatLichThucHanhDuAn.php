<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatLichThucHanhDuAn extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatLichThucHanhDuAn';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaHoc',
        'HangDaoTao',
        'NgayKhaiGiang',
        'NgayKetThucDuKien',
        'CauHinhJson',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayKhaiGiang' => 'date',
        'NgayKetThucDuKien' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    /** @return array<string, mixed> */
    public function cauHinh(): array
    {
        $raw = json_decode((string) ($this->CauHinhJson ?? '{}'), true);

        return is_array($raw) ? $raw : [];
    }

    public function setCauHinhArray(array $cauHinh): void
    {
        $this->CauHinhJson = json_encode($cauHinh, JSON_UNESCAPED_UNICODE);
    }

    /** @return HasMany<DatLichThucHanhPhienBan, $this> */
    public function phienBans(): HasMany
    {
        return $this->hasMany(DatLichThucHanhPhienBan::class, 'DuAnId', 'Id');
    }
}
