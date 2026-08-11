<?php

namespace App\Models\PMGPLXOLD;

use Illuminate\Database\Eloquent\Model;

class KhoaHoc extends Model
{
    protected $connection = 'sqlsrv_old';

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
