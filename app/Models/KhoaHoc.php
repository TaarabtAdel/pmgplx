<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhoaHoc extends Model
{
    protected $table = 'KhoaHoc';

    protected $primaryKey = 'MaKH';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayKG' => 'datetime',
        'NgayBG' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('TrangThai', 1);
    }
}
