<?php

namespace App\Models\DaoTao;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một dòng thu (SoTien dương) hoặc trừ (âm) của một khoản tiền học viên. */
class ThuTienHocVienGiaoDich extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'ThuTienHocVienGiaoDich';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    /** Sinh khi đổi trạng thái thu tiền / kết quả thi. */
    public const TU_DONG = 'tu_dong';

    /** Người dùng nhập tay (thu thêm, điều chỉnh) — xoá được. */
    public const THU_CONG = 'thu_cong';

    protected $fillable = [
        'ThuTienHocVienId',
        'Khoan',
        'SoTien',
        'Nguon',
        'NoiDung',
        'NguoiTaoId',
        'NgayTao',
    ];

    protected $casts = [
        'SoTien' => 'integer',
        'NgayTao' => 'datetime',
    ];

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiTaoId', 'Id');
    }
}
