<?php

namespace App\Models\PMGPLXMOTO;

use Illuminate\Database\Eloquent\Model;

class XeTap extends Model
{
    protected $connection = 'sqlsrv_moto';
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
