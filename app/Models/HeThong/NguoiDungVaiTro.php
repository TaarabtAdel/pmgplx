<?php

namespace App\Models\HeThong;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NguoiDungVaiTro extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'NguoiDungVaiTro';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'NguoiDungId',
        'MaVaiTro',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiDungId', 'Id');
    }
}
