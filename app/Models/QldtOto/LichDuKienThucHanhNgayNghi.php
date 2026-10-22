<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;

class LichDuKienThucHanhNgayNghi extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichDuKienThucHanhNgayNghi';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichDuKienId',
        'Ngay',
        'GhiChu',
    ];

    protected $casts = [
        'Ngay' => 'date',
    ];
}
