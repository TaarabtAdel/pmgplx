<?php

namespace App\Models\DaoTao;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kết quả «Dò thông tin» CCCD / GPLX của một học viên (MaDK bên PMGPLX), lưu trên MANHLINH.
 * Hiện chỉ lưu đối chiếu từng trường với PMGPLX (cột Khop*: 1 khớp / 0 không khớp / NULL chưa dò)
 * và kết luận người dò chọn (KetLuan: ok / can_chinh — có khi chỉ cần khớp vài trường là chấp nhận);
 * các cột giá trị (HoTen, SoCCCD…) để dành, chưa ghi. NguoiLX / NguoiLX_HoSo (PMGPLX) giữ nguyên.
 */
class HocVienDoThongTin extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'HocVienDoThongTin';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    /** Trường dò (khoá dùng ở modal) => cột trạng thái khớp. */
    public const COT_KHOP = [
        'ho_ten' => 'KhopHoTen',
        'so_cccd' => 'KhopSoCCCD',
        'ngay_sinh' => 'KhopNgaySinh',
        'noi_thuong_tru' => 'KhopNoiThuongTru',
        'ngay_cap_cccd' => 'KhopNgayCapCCCD',
        'so_gplx' => 'KhopSoGPLX',
        'ngay_cap_gplx' => 'KhopNgayCapGPLX',
        'ngay_trung_tuyen' => 'KhopNgayTrungTuyen',
    ];

    public const OK = 'ok';

    public const CAN_CHINH = 'can_chinh';

    public const NHAN_KET_LUAN = [self::OK => 'OK', self::CAN_CHINH => 'Cần chỉnh'];

    protected $fillable = [
        'MaDK',
        'MaKhoaHoc',
        'KetLuan',
        'KhopHoTen',
        'KhopSoCCCD',
        'KhopNgaySinh',
        'KhopNoiThuongTru',
        'KhopNgayCapCCCD',
        'KhopSoGPLX',
        'KhopNgayCapGPLX',
        'KhopNgayTrungTuyen',
        'NguoiCapNhatId',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'KhopHoTen' => 'boolean',
        'KhopSoCCCD' => 'boolean',
        'KhopNgaySinh' => 'boolean',
        'KhopNoiThuongTru' => 'boolean',
        'KhopNgayCapCCCD' => 'boolean',
        'KhopSoGPLX' => 'boolean',
        'KhopNgayCapGPLX' => 'boolean',
        'KhopNgayTrungTuyen' => 'boolean',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function nguoiCapNhat(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'NguoiCapNhatId', 'Id');
    }

    /** @return array<string, bool> khoá modal => khớp?, chỉ các trường đã dò */
    public function trangThai(): array
    {
        $out = [];
        foreach (self::COT_KHOP as $key => $cot) {
            if ($this->{$cot} !== null) {
                $out[$key] = (bool) $this->{$cot};
            }
        }

        return $out;
    }

    public function soTruongLech(): int
    {
        return count(array_filter($this->trangThai(), fn (bool $khop) => ! $khop));
    }
}
