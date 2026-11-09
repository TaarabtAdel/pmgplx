<?php

namespace App\Models\PMGPLXMOTO;

use Illuminate\Database\Eloquent\Model;

class DmMonHoc extends Model
{
    protected $connection = 'sqlsrv_moto';
    protected $table = 'DM_MonHoc';

    protected $primaryKey = 'MaMH';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('TrangThai', 1);
    }
}
