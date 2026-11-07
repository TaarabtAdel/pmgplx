<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;

/** Loại khoá gán riêng cho một khoá thực tế PMGPLX (theo mã khoá-lớp, vd. B01K41). */
class TienDoThucTeLoaiKhoa extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'TienDoThucTeLoaiKhoa';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoaLop',
        'TenLoaiKhoa',
        'NgayTao',
    ];

    protected $casts = [
        'NgayTao' => 'datetime',
    ];
}
