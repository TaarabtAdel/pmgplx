<?php

namespace App\Models\PMGPLX;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NguoiLXHoSo extends Model
{
    protected $table = 'NguoiLX_HoSo';

    protected $primaryKey = 'MaDK';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public function nguoiLX(): BelongsTo
    {
        return $this->belongsTo(NguoiLX::class, 'MaDK', 'MaDK');
    }
}
