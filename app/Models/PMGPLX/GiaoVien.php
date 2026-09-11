<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GiaoVien extends Model
{
    protected $table = 'GiaoVien';

    protected $primaryKey = 'MaGV';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayCapGPLX' => 'datetime',
        'NgayQD_GCN' => 'datetime',
        'NgayHHGPLX' => 'datetime',
    ];

    public function getHoTenAttribute(): string
    {
        return trim(($this->HoTenDem ?? '').' '.($this->TenGV ?? ''));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeTimTheoTuKhoa($query, string $keyword)
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return $query;
        }

        $like = '%'.$keyword.'%';
        $fullNameSql = "(LTRIM(RTRIM(ISNULL(HoTenDem, ''))) + ' ' + LTRIM(RTRIM(ISNULL(TenGV, ''))))";

        return $query->where(function ($q) use ($keyword, $like, $fullNameSql): void {
            $q->where('MaGV', 'like', $like)
                ->orWhere('TenGV', 'like', $like)
                ->orWhere('HoTenDem', 'like', $like)
                ->orWhere('SoCMT', 'like', $like)
                ->orWhere('DienThoai', 'like', $like)
                ->orWhereRaw("{$fullNameSql} LIKE ?", [$like]);

            $tokens = preg_split('/\s+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($tokens) > 1) {
                $q->orWhere(function ($sub) use ($tokens, $fullNameSql): void {
                    foreach ($tokens as $token) {
                        $sub->whereRaw("{$fullNameSql} LIKE ?", ['%'.$token.'%']);
                    }
                });
            }
        });
    }
}
