<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;

class LichChinhThucNgayNghi extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichChinhThucNgayNghi';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichChinhThucId',
        'Ngay',
        'GhiChu',
    ];

    protected $casts = [
        'Ngay' => 'date',
    ];
}
