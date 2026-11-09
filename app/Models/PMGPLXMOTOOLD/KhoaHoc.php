<?php

namespace App\Models\PMGPLXMOTOOLD;

use Illuminate\Database\Eloquent\Model;

class KhoaHoc extends Model
{
    protected $connection = 'sqlsrv_moto_old';

    protected $table = 'KhoaHoc';

    protected $primaryKey = 'MaKH';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayKG' => 'datetime',
        'NgayBG' => 'datetime',
    ];
}
