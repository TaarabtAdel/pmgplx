<?php

namespace App\Models\DaoTao;

use App\Models\HeThong\NguoiDung;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kết quả «Dò thông tin» CCCD / GPLX đã duyệt cho một học viên (MaDK bên PMGPLX).
 * Lưu trên MANHLINH; NguoiLX / NguoiLX_HoSo (PMGPLX) giữ nguyên.
 */
class HocVienDoThongTin extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'HocVienDoThongTin';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    /** Trường dò (khoá dùng ở modal) => [cột, là ngày]. */
    public const COT = [
        'ho_ten' => ['HoTen', false],
        'so_cccd' => ['SoCCCD', false],
        'ngay_sinh' => ['NgaySinh', true],
        'noi_thuong_tru' => ['NoiThuongTru', false],
        'ngay_cap_cccd' => ['NgayCapCCCD', true],
        'so_gplx' => ['SoGPLX', false],
        'ngay_cap_gplx' => ['NgayCapGPLX', true],
        'ngay_trung_tuyen' => ['NgayTrungTuyen', true],
    ];

    protected $fillable = [
        'MaDK',
        'MaKhoaHoc',
        'HoTen',
        'SoCCCD',
        'NgaySinh',
        'NoiThuongTru',
        'NgayCapCCCD',
        'SoGPLX',
        'NgayCapGPLX',
        'NgayTrungTuyen',
        'NguoiCapNhatId',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgaySinh' => 'date',
        'NgayCapCCCD' => 'date',
        'NgayCapGPLX' => 'date',
        'NgayTrungTuyen' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function nguoiCapNhat(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiCapNhatId', 'Id');
    }

    /** Giá trị đã lưu theo khoá modal (ngày dạng dd/mm/yyyy), chỉ các trường đã lưu. */
    public function giaTri(): array
    {
        $out = [];
        foreach (self::COT as $key => [$cot, $laNgay]) {
            $v = $this->{$cot};
            if ($v === null || $v === '') {
                continue;
            }
            $out[$key] = $laNgay && $v instanceof Carbon ? $v->format('d/m/Y') : (string) $v;
        }

        return $out;
    }
}
