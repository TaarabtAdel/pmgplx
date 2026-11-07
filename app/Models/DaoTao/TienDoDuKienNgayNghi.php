<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

class TienDoDuKienNgayNghi extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'TienDoDuKienNgayNghi';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'TenNgayNghi',
        'TuNgay',
        'DenNgay',
        'NgayTao',
    ];

    protected $casts = [
        'TuNgay' => 'date',
        'DenNgay' => 'date',
        'NgayTao' => 'datetime',
    ];
}
