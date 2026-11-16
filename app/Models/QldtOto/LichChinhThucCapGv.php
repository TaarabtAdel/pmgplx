<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;

class LichChinhThucCapGv extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichChinhThucCapGv';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichChinhThucId',
        'ThuTu',
        'MaGvSang',
        'MaGvChieu',
        'BienSoXe',
    ];

    protected $casts = [
        'ThuTu' => 'integer',
    ];
}
