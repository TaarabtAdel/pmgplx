<?php

namespace App\Models\HeThong;

use Illuminate\Database\Eloquent\Model;

/** Vai trò (MaVaiTro) được dùng chức năng (MaChucNang = khoá lá của CayChucNang). Admin không cần dòng ở đây. */
class PhanQuyen extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'PhanQuyen';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaVaiTro',
        'MaChucNang',
        'NguoiCapNhatId',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayCapNhat' => 'datetime',
    ];
}
