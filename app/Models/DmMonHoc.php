<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DmMonHoc extends Model
{
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
