<?php

namespace App\Models\DaoTao;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Cập nhật GPLX học viên. Bảng CapNhatGPLXHocVien (MANHLINH) chỉ giữ MaDK + trạng thái / số GPLX mới;
 * thông tin học viên và GPLX đã có lấy từ NguoiLX / NguoiLX_HoSo (PMGPLX) theo MaDK — không sửa DB GPLX_BAN_MOI.
 */
class CapNhatGPLXHocVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'CapNhatGPLXHocVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaDK',
        'MaKhoaHoc',
        'DaCapNhatGPLX',
        'NgayCapNhatGPLX',
        'DaXuLy',
        'SoGPLX',
        'HangGPLX',
        'NgayCapGPLX',
        'NgayHHGPLX',
        'NoiCapGPLX',
        'GhiChu',
        'NguoiTaoId',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'DaCapNhatGPLX' => 'boolean',
        'DaXuLy' => 'boolean',
        'NgayCapNhatGPLX' => 'datetime',
        'NgayCapGPLX' => 'date',
        'NgayHHGPLX' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    /**
     * @param  array<string, mixed>  $thayDoi
     */
    public function capNhatTrangThai(array $thayDoi): void
    {
        $now = Carbon::now();
        $daCapCu = (bool) $this->DaCapNhatGPLX;

        if (array_key_exists('DaCapNhatGPLX', $thayDoi) && (bool) $thayDoi['DaCapNhatGPLX'] !== $daCapCu) {
            $this->DaCapNhatGPLX = (bool) $thayDoi['DaCapNhatGPLX'];
            $this->NgayCapNhatGPLX = $this->DaCapNhatGPLX ? $now : null;
            if ($this->DaCapNhatGPLX && ! array_key_exists('DaXuLy', $thayDoi)) {
                $this->DaXuLy = true;
            }
        }

        $this->fill(array_diff_key($thayDoi, array_flip(['DaCapNhatGPLX'])));
        $this->NgayCapNhat = $now;
        $this->save();
    }
}
