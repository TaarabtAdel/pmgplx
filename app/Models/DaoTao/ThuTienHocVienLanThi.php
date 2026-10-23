<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

/** Một lần thi một môn của học viên (lần 1, thi lại lần 2, 3…). */
class ThuTienHocVienLanThi extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'ThuTienHocVienLanThi';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'ThuTienHocVienId',
        'Khoan',
        'LanThi',
        'KetQua',
        'NgayThi',
        'NguoiTaoId',
        'NgayTao',
    ];

    protected $casts = [
        'LanThi' => 'integer',
        'NgayThi' => 'date',
        'NgayTao' => 'datetime',
    ];
}
