<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;

class XeTap extends Model
{
    protected $table = 'XeTap';

    protected $primaryKey = 'BienSoXe';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
        'SoHuu' => 'boolean',
        'GiayPhepXTL' => 'boolean',
        'BaoHiem' => 'boolean',
        'HeThongPP' => 'boolean',
        'NgayCapGPXTL' => 'datetime',
        'NgayHHGPXTL' => 'datetime',
        'NgayCapGCNKD' => 'datetime',
        'NgayHHGCNKD' => 'datetime',
    ];
}
