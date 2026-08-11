<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;

class GiaoVien extends Model
{
    protected $table = 'GiaoVien';

    protected $primaryKey = 'MaGV';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayCapGPLX' => 'datetime',
        'NgayQD_GCN' => 'datetime',
        'NgayHHGPLX' => 'datetime',
    ];

    public function getHoTenAttribute(): string
    {
        return trim(($this->HoTenDem ?? '').' '.($this->TenGV ?? ''));
    }
}
