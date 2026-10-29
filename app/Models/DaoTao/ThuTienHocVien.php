<?php

namespace App\Models\DaoTao;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Thu tiền học viên. Số tiền từng khoản = tổng giao dịch (ThuTienHocVienGiaoDich) = tiền học viên đang giữ cho khoản đó.
 *
 * Đổi trạng thái (đã thu tiền, kết quả thi) tự ghi giao dịch theo quy tắc:
 *   đã thu = +phí; có kết quả thi (đậu hoặc rớt) = -phí (đã dùng phí thi).
 *   Rớt thì phải thu lại phí thi lại: phaiThu() = phí - số tiền đang giữ (hiện "Rớt (-100.000)").
 *   Ví dụ lý thuyết 100k: đã thu, chưa thi = 100k; đậu = 0; rớt = 0 và phải thu 100k; thu lại = 100k.
 * Mỗi lần thi (ThuTienHocVienLanThi: lần 1, thi lại lần 2, 3…) trừ phí môn đó một lần; KetQua* = kết quả lần mới nhất.
 * Huỷ lần thi cuối (nhập nhầm) thì hoàn lại phí.
 */
class ThuTienHocVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'ThuTienHocVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    /** Phí từng khoản (đồng). Môn thi => cột kết quả; 'bia' không có kết quả thi. */
    public const PHI = [
        'ly_thuyet' => 100000,
        'sa_hinh' => 350000,
        'duong' => 80000,
        'bia' => 115000,
    ];

    public const COT_KET_QUA = [
        'ly_thuyet' => 'KetQuaLyThuyet',
        'sa_hinh' => 'KetQuaSaHinh',
        'duong' => 'KetQuaDuong',
    ];

    public const NHAN_MON = [
        'ly_thuyet' => 'Lý thuyết',
        'sa_hinh' => 'Sa hình',
        'duong' => 'Đường',
        'bia' => 'Bìa',
    ];

    public const DAU = 'dau';

    public const ROT = 'rot';

    public const NHAN_KQ = [self::DAU => 'Đậu', self::ROT => 'Rớt'];

    protected $fillable = [
        'MaDK',
        'MaKhoaHoc',
        'SoDienThoai',
        'DaTotNghiep',
        'DaThuTien',
        'DaXuLy',
        'NgayThuTien',
        'KetQuaLyThuyet',
        'KetQuaSaHinh',
        'KetQuaDuong',
        'GhiChu',
        'NguoiTaoId',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'DaTotNghiep' => 'boolean',
        'DaThuTien' => 'boolean',
        'DaXuLy' => 'boolean',
        'NgayThuTien' => 'datetime',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function giaoDichs(): HasMany
    {
        return $this->hasMany(ThuTienHocVienGiaoDich::class, 'ThuTienHocVienId', 'Id');
    }

    public function lanThis(): HasMany
    {
        return $this->hasMany(ThuTienHocVienLanThi::class, 'ThuTienHocVienId', 'Id');
    }

    /** Số lần đã thi một môn (dùng quan hệ lanThis nếu đã load). */
    public function soLanThi(string $khoan): int
    {
        $ds = $this->relationLoaded('lanThis') ? $this->lanThis : $this->lanThis()->get();

        return $ds->where('Khoan', $khoan)->count();
    }

    /** Nhãn kết quả hiện tại một môn: "Đậu", "Đậu (lần 2)", "Rớt (-100.000)", "Rớt lần 3 (-100.000)", "Rớt · đã thu lại". */
    public function nhanKetQua(string $khoan): string
    {
        $kq = $this->{self::COT_KET_QUA[$khoan]};
        if ($kq === null) {
            return '';
        }
        $lan = $this->soLanThi($khoan);
        if ($kq === self::DAU) {
            return 'Đậu'.($lan > 1 ? ' (lần '.$lan.')' : '');
        }
        $canThu = $this->phaiThu($khoan);

        return 'Rớt'.($lan > 1 ? ' lần '.$lan : '')
            .($canThu > 0 ? ' ('.self::dinhDangTien(-$canThu).')' : ' · đã thu lại');
    }

    /**
     * Ghi lần thi tiếp theo của một môn: trừ phí môn đó, cập nhật kết quả mới nhất.
     * Gọi trong transaction của kết nối sqlsrv_manhlinh.
     */
    public function themLanThi(string $khoan, string $ketQua, ?string $ngayThi, ?int $nguoiId): ThuTienHocVienLanThi
    {
        $cot = self::COT_KET_QUA[$khoan];
        if ($this->{$cot} === self::DAU) {
            throw new \DomainException(self::NHAN_MON[$khoan].' đã đậu, không thêm lần thi được.');
        }
        $now = Carbon::now();
        $lan = (int) $this->lanThis()->where('Khoan', $khoan)->max('LanThi') + 1;
        $lanThi = $this->lanThis()->create([
            'Khoan' => $khoan,
            'LanThi' => $lan,
            'KetQua' => $ketQua,
            'NgayThi' => $ngayThi,
            'NguoiTaoId' => $nguoiId,
            'NgayTao' => $now,
        ]);
        $phi = self::PHI[$khoan];
        $this->giaoDichs()->create([
            'Khoan' => $khoan,
            'SoTien' => -$phi,
            'Nguon' => ThuTienHocVienGiaoDich::TU_DONG,
            'NoiDung' => 'KQ '.self::NHAN_MON[$khoan].' lần '.$lan.': '
                .($ketQua === self::ROT ? 'Rớt ('.self::dinhDangTien(-$phi).')' : 'Đậu'),
            'NguoiTaoId' => $nguoiId,
            'NgayTao' => $now,
        ]);
        $this->{$cot} = $ketQua;
        $this->NgayCapNhat = $now;
        $this->save();
        $this->capNhatTrangThaiXuLyTuKetQua();

        return $lanThi;
    }

    /** Huỷ lần thi cuối của một môn (nhập nhầm): hoàn phí, kết quả về lần trước (hoặc chưa thi). */
    public function huyLanThiCuoi(string $khoan, ?int $nguoiId): void
    {
        $cuoi = $this->lanThis()->where('Khoan', $khoan)->orderByDesc('LanThi')->first();
        if (! $cuoi) {
            throw new \DomainException(self::NHAN_MON[$khoan].' chưa có lần thi nào.');
        }
        $now = Carbon::now();
        $cuoi->delete();
        $this->giaoDichs()->create([
            'Khoan' => $khoan,
            'SoTien' => self::PHI[$khoan],
            'Nguon' => ThuTienHocVienGiaoDich::TU_DONG,
            'NoiDung' => 'Huỷ KQ '.self::NHAN_MON[$khoan].' lần '.$cuoi->LanThi.' ('.self::NHAN_KQ[$cuoi->KetQua].') — hoàn phí thi',
            'NguoiTaoId' => $nguoiId,
            'NgayTao' => $now,
        ]);
        $this->{self::COT_KET_QUA[$khoan]} = $this->lanThis()->where('Khoan', $khoan)->orderByDesc('LanThi')->value('KetQua');
        $this->NgayCapNhat = $now;
        $this->save();
        $this->capNhatTrangThaiXuLyTuKetQua();
    }

    /** Cả 3 môn LT / Sa hình / Đường đều đậu. */
    public function duDieuKienTuDongXuLy(): bool
    {
        foreach (self::COT_KET_QUA as $cot) {
            if ($this->{$cot} !== self::DAU) {
                return false;
            }
        }

        return true;
    }

    /** Tự bật «Đã xử lý» khi đủ 3 môn đậu; tắt khi không còn đủ (vd huỷ lần thi). */
    public function capNhatTrangThaiXuLyTuKetQua(): void
    {
        if ($this->duDieuKienTuDongXuLy()) {
            if ($this->DaXuLy) {
                return;
            }
            $this->DaXuLy = true;
        } else {
            if (! $this->DaXuLy) {
                return;
            }
            $this->DaXuLy = false;
        }
        $this->NgayCapNhat = Carbon::now();
        $this->save();
    }

    /** Số dư một khoản: lấy từ cột so_du_* (scopeKemSoDu) nếu có, không thì cộng giao dịch. */
    public function tien(string $khoan): int
    {
        $cot = 'so_du_'.$khoan;
        if (array_key_exists($cot, $this->attributes)) {
            return (int) $this->attributes[$cot];
        }

        return (int) $this->giaoDichs()->where('Khoan', $khoan)->sum('SoTien');
    }

    public function tongTien(): int
    {
        return array_sum(array_map(fn (string $k): int => $this->tien($k), array_keys(self::PHI)));
    }

    /** Biểu thức SQL số dư một khoản của dòng ThuTienHocVien hiện tại (dùng lọc / sắp xếp). */
    public static function sqlSoDu(string $khoan): string
    {
        abort_unless(isset(self::PHI[$khoan]), 500);

        return "(SELECT COALESCE(SUM(g.SoTien), 0) FROM ThuTienHocVienGiaoDich g WHERE g.ThuTienHocVienId = ThuTienHocVien.Id AND g.Khoan = '{$khoan}')";
    }

    /**
     * Thêm cột so_du_{khoản} vào kết quả để không phải truy vấn từng dòng.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeKemSoDu(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('ThuTienHocVien.*');
        }
        foreach (array_keys(self::PHI) as $k) {
            $query->selectRaw(self::sqlSoDu($k).' AS so_du_'.$k);
        }

        return $query;
    }

    /**
     * Tổng từng khoản của cả danh sách đang lọc.
     *
     * @param  Builder<static>  $query
     * @return array<string, int>
     */
    public static function tongTheoKhoan(Builder $query): array
    {
        $tong = ThuTienHocVienGiaoDich::query()
            ->whereIn('ThuTienHocVienId', (clone $query)->select('Id')->reorder()->toBase())
            ->groupBy('Khoan')
            ->selectRaw('Khoan, SUM(SoTien) AS tong')
            ->pluck('tong', 'Khoan');
        $out = [];
        foreach (array_keys(self::PHI) as $k) {
            $out[$k] = (int) ($tong[$k] ?? 0);
        }
        $out['tong'] = array_sum($out);

        return $out;
    }

    /** Số tiền một khoản theo trạng thái (đã thu + đã thi) — dùng để tính phần chênh khi đổi trạng thái. */
    public function tienTheoTrangThai(string $khoan): int
    {
        $phi = self::PHI[$khoan];
        $tien = $this->DaThuTien ? $phi : 0;
        $kq = isset(self::COT_KET_QUA[$khoan]) ? $this->{self::COT_KET_QUA[$khoan]} : null;

        return $tien - (in_array($kq, [self::DAU, self::ROT], true) ? $phi : 0);
    }

    /** Số tiền còn phải thu của một khoản: rớt thì cần đủ phí thi lại; khoản âm thì phải bù phần âm. */
    public function phaiThu(string $khoan): int
    {
        $soDu = $this->tien($khoan);
        $kq = isset(self::COT_KET_QUA[$khoan]) ? $this->{self::COT_KET_QUA[$khoan]} : null;

        return max(0, $kq === self::ROT ? self::PHI[$khoan] - $soDu : -$soDu);
    }

    public function tongPhaiThu(): int
    {
        return array_sum(array_map(fn (string $k): int => $this->phaiThu($k), array_keys(self::PHI)));
    }

    /** Biểu thức SQL số tiền phải thu một khoản (cùng công thức phaiThu). */
    public static function sqlPhaiThu(string $khoan): string
    {
        $soDu = self::sqlSoDu($khoan);
        if (! isset(self::COT_KET_QUA[$khoan])) {
            return "(CASE WHEN {$soDu} < 0 THEN -{$soDu} ELSE 0 END)";
        }
        $cot = self::COT_KET_QUA[$khoan];
        $phi = self::PHI[$khoan];
        $can = "(CASE WHEN ThuTienHocVien.{$cot} = '".self::ROT."' THEN {$phi} - {$soDu} ELSE -{$soDu} END)";

        return "(CASE WHEN {$can} > 0 THEN {$can} ELSE 0 END)";
    }

    /**
     * Tổng phải thu từng khoản của cả danh sách đang lọc.
     *
     * @param  Builder<static>  $query
     * @return array<string, int>
     */
    public static function tongPhaiThuTheoKhoan(Builder $query): array
    {
        // SQL Server không cho SUM trên biểu thức có subquery: tính từng dòng trong bảng tạm rồi mới cộng.
        $tungDong = (clone $query)->reorder()->toBase();
        $tungDong->columns = null;
        $tong = [];
        foreach (array_keys(self::PHI) as $k) {
            $tungDong->selectRaw(self::sqlPhaiThu($k).' AS '.$k);
            $tong[] = 'SUM('.$k.') AS '.$k;
        }
        $row = $tungDong->newQuery()->fromSub($tungDong, 't')->selectRaw(implode(', ', $tong))->first();
        $out = [];
        foreach (array_keys(self::PHI) as $k) {
            $out[$k] = (int) ($row->{$k} ?? 0);
        }
        $out['tong'] = array_sum($out);

        return $out;
    }

    /**
     * Đổi trạng thái (đã thu tiền, tốt nghiệp, ghi chú) rồi ghi giao dịch chênh lệch cho các khoản bị ảnh hưởng.
     * Gọi trong transaction của kết nối sqlsrv_manhlinh.
     *
     * @param  array<string, mixed>  $thayDoi  cột => giá trị mới (DaThuTien, DaTotNghiep, GhiChu…)
     */
    public function capNhatTrangThai(array $thayDoi, ?int $nguoiId): void
    {
        $now = Carbon::now();
        $ghi = function (string $khoan, int $soTien, string $noiDung) use ($nguoiId, $now): void {
            $this->giaoDichs()->create([
                'Khoan' => $khoan,
                'SoTien' => $soTien,
                'Nguon' => ThuTienHocVienGiaoDich::TU_DONG,
                'NoiDung' => $noiDung,
                'NguoiTaoId' => $nguoiId,
                'NgayTao' => $now,
            ]);
        };

        $daThuCu = (bool) $this->DaThuTien;
        if (array_key_exists('DaThuTien', $thayDoi) && (bool) $thayDoi['DaThuTien'] !== $daThuCu) {
            $truoc = array_map(fn (string $k): int => $this->tienTheoTrangThai($k), array_combine(array_keys(self::PHI), array_keys(self::PHI)));
            $this->DaThuTien = (bool) $thayDoi['DaThuTien'];
            $this->NgayThuTien = $this->DaThuTien ? $now : null;
            foreach ($truoc as $k => $cu) {
                $chenh = $this->tienTheoTrangThai($k) - $cu;
                if ($chenh !== 0) {
                    $ghi($k, $chenh, $this->DaThuTien ? 'Đã thu tiền' : 'Bỏ đã thu tiền');
                }
            }
        }

        // Kết quả thi đổi qua themLanThi / huyLanThiCuoi, không sửa trực tiếp ở đây.
        $this->fill(array_diff_key($thayDoi, array_flip(['DaThuTien', ...array_values(self::COT_KET_QUA)])));
        $this->NgayCapNhat = $now;
        $this->save();
    }

    public static function dinhDangTien(int $v): string
    {
        return number_format($v, 0, ',', '.');
    }

    /** PMGPLX: NguoiLX_HoSo.KetLuanCSDT = 1 (Đạt). */
    public static function daTotNghiepTuHoSo(?object $hoSoRow): bool
    {
        if ($hoSoRow === null) {
            return false;
        }

        return (int) ($hoSoRow->KetLuanCSDT ?? 0) === 1;
    }

    /** Hiển thị / lọc: đã tốt nghiệp thủ công (DaTotNghiep) hoặc KetLuanCSDT = 1. */
    public static function daTotNghiepHienThi(?object $hoSoRow, bool $daTotNghiep): bool
    {
        return $daTotNghiep || self::daTotNghiepTuHoSo($hoSoRow);
    }

    /** Hạng đào tạo PMGPLX: NguoiLX_HoSo.HangDaoTao, fallback KhoaHoc.HangDT. */
    public static function hangDaoTaoTuHoSo(?object $hoSoRow): string
    {
        if ($hoSoRow === null) {
            return '';
        }
        $hang = trim((string) ($hoSoRow->HangDaoTao ?? ''));
        if ($hang !== '') {
            return $hang;
        }

        return trim((string) ($hoSoRow->HangDTKhoa ?? ''));
    }
}
