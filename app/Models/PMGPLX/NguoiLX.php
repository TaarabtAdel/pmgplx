<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NguoiLX extends Model
{
    protected $table = 'NguoiLX';

    protected $primaryKey = 'MaDK';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'NgayCapCMT' => 'datetime',
        'NgayTao' => 'datetime',
        'NgaySua' => 'datetime',
    ];

    public function getHoTenAttribute(): string
    {
        return trim((string) ($this->HoVaTen ?: trim(($this->HoDemNLX ?? '').' '.($this->TenNLX ?? ''))));
    }

    public function hoSo(): HasOne
    {
        return $this->hasOne(NguoiLXHoSo::class, 'MaDK', 'MaDK');
    }
}
