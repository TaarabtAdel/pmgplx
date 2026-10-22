<?php

namespace App\Models\QldtOto;

use Illuminate\Database\Eloquent\Model;

/** Một cặp giáo viên sáng / chiều dùng chung một xe trong lịch dự kiến thực hành. */
class LichDuKienThucHanhCapGv extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'QldtOtoLichDuKienThucHanhCapGv';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'LichDuKienId',
        'ThuTu',
        'MaGvSang',
        'MaGvChieu',
        'BienSoXe',
    ];

    protected $casts = [
        'ThuTu' => 'integer',
    ];
}
