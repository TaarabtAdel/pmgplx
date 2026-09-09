<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DatPhanLoaiPhien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatPhanLoaiPhien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'TenPhanLoai',
        'MoTa',
        'ThuTu',
        'NgayTao',
    ];

    protected $casts = [
        'ThuTu' => 'integer',
        'NgayTao' => 'datetime',
    ];

    public function phienHoc(): BelongsToMany
    {
        return $this->belongsToMany(
            DatDSPhien::class,
            'DatDSPhienPhanLoai',
            'PhanLoaiId',
            'DatDSPhienId'
        );
    }
}
