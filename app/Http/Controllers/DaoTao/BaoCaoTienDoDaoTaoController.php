<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\TienDoDuKienLoaiKhoa;
use App\Models\DaoTao\TienDoDuKienNgayNghi;
use App\Models\DaoTao\TienDoDuKienTangXe;
use App\Models\DaoTao\TienDoThucTeLoaiKhoa;
use App\Support\DaoTao\TienDoDaoTaoNam;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BaoCaoTienDoDaoTaoController extends Controller
{
    /** Giá trị gợi ý khi thêm loại khoá theo hạng. */
    public const MAC_DINH_THEO_HANG = [
        'B01' => ['so_hoc_vien_toi_da' => 100, 'so_ngay_ly_thuyet' => 17, 'so_ngay_thuc_hanh' => 45, 'so_hoc_vien_moi_xe' => 5],
        'C' => ['so_hoc_vien_toi_da' => 48, 'so_ngay_ly_thuyet' => 19, 'so_ngay_thuc_hanh' => 45, 'so_hoc_vien_moi_xe' => 8],
        'B' => ['so_hoc_vien_toi_da' => 100, 'so_ngay_ly_thuyet' => 19, 'so_ngay_thuc_hanh' => 47, 'so_hoc_vien_moi_xe' => 5],
    ];

    public function index(Request $request): View
    {
        $namHienTai = (int) now()->year;
        $nam = (int) $request->input('nam', $namHienTai);
        if ($nam < 2020 || $nam > $namHienTai + 5) {
            $nam = $namHienTai;
        }

        try {
            $ngayXem = Carbon::parse((string) $request->input('ngay_xem', "{$nam}-12-31"))->startOfDay();
        } catch (\Throwable) {
            $ngayXem = Carbon::create($nam, 12, 31)->startOfDay();
        }

        $tienDo = (new TienDoDaoTaoNam($nam))->lap();

        return view('DaoTao.bao-cao.tien-do-dao-tao', [
            'nam' => $nam,
            'namOptions' => range(2025, $namHienTai + 2),
            'ngayXem' => $ngayXem,
            'khoa' => $tienDo['khoa'],
            'canhBao' => $tienDo['canh_bao'],
            'tongHop' => $tienDo['tong_hop'],
            'dangHoc' => TienDoDaoTaoNam::dangHocTaiNgay($tienDo['tat_ca'], $ngayXem),
            'loaiKhoa' => TienDoDuKienLoaiKhoa::query()->orderBy('ThuTu')->orderBy('Id')->get(),
            'mauTheoTenLoai' => TienDoDuKienLoaiKhoa::query()->pluck('MauSac', 'TenLoai'),
            'tangXe' => TienDoDuKienTangXe::query()->with('loaiKhoa')->orderBy('TuNgay')->get(),
            'ngayNghi' => TienDoDuKienNgayNghi::query()->orderBy('TuNgay')->get(),
            'nhanHang' => TienDoDaoTaoNam::NHAN_HANG,
            'macDinhTheoHang' => self::MAC_DINH_THEO_HANG,
        ]);
    }

    public function storeLoaiKhoa(Request $request): RedirectResponse
    {
        $thuTu = (int) (TienDoDuKienLoaiKhoa::query()->max('ThuTu') ?? 0) + 1;
        TienDoDuKienLoaiKhoa::query()->create([
            ...$this->loaiKhoaTuRequest($request),
            'ThuTu' => $thuTu,
            'NgayTao' => now(),
        ]);

        return back()->with('success', 'Đã thêm loại khoá dự kiến.');
    }

    public function updateLoaiKhoa(Request $request, int $id): RedirectResponse
    {
        $loai = TienDoDuKienLoaiKhoa::query()->findOrFail($id);
        $loai->update([...$this->loaiKhoaTuRequest($request), 'NgayCapNhat' => now()]);

        return back()->with('success', "Đã cập nhật loại khoá «{$loai->TenLoai}».");
    }

    public function destroyLoaiKhoa(int $id): RedirectResponse
    {
        $loai = TienDoDuKienLoaiKhoa::query()->findOrFail($id);
        $loai->delete();

        return back()->with('success', "Đã xoá loại khoá «{$loai->TenLoai}».");
    }

    public function storeTangXe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'loai_khoa_id' => ['required', 'integer', Rule::exists('sqlsrv_manhlinh.TienDoDuKienLoaiKhoa', 'Id')],
            'tu_ngay' => ['required', 'date'],
            'so_gv' => ['required', 'integer', 'between:-100,100', 'not_in:0'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'loai_khoa_id.required' => 'Chọn loại khoá.',
            'tu_ngay.required' => 'Nhập ngày bắt đầu áp dụng.',
            'so_gv.not_in' => 'Số GV tăng/giảm phải khác 0.',
        ]);

        $soGv = (int) $validated['so_gv'];

        TienDoDuKienTangXe::query()->create([
            'LoaiKhoaId' => (int) $validated['loai_khoa_id'],
            'TuNgay' => $validated['tu_ngay'],
            'SoXe' => $soGv,
            'GhiChu' => trim((string) ($validated['ghi_chu'] ?? '')) ?: null,
            'NgayTao' => now(),
        ]);

        return back()->with('success', 'Đã thêm thay đổi số GV.');
    }

    public function destroyTangXe(int $id): RedirectResponse
    {
        TienDoDuKienTangXe::query()->findOrFail($id)->delete();

        return back()->with('success', 'Đã xoá thay đổi số GV.');
    }

    public function storeNgayNghi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ten_ngay_nghi' => ['required', 'string', 'max:150'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['nullable', 'date', 'after_or_equal:tu_ngay'],
        ], [
            'ten_ngay_nghi.required' => 'Nhập tên ngày nghỉ.',
            'tu_ngay.required' => 'Nhập ngày bắt đầu nghỉ.',
            'den_ngay.after_or_equal' => 'Ngày kết thúc nghỉ phải từ ngày bắt đầu trở đi.',
        ]);

        TienDoDuKienNgayNghi::query()->create([
            'TenNgayNghi' => trim((string) $validated['ten_ngay_nghi']),
            'TuNgay' => $validated['tu_ngay'],
            'DenNgay' => $validated['den_ngay'] ?? $validated['tu_ngay'],
            'NgayTao' => now(),
        ]);

        return back()->with('success', 'Đã thêm ngày nghỉ.');
    }

    public function destroyNgayNghi(int $id): RedirectResponse
    {
        TienDoDuKienNgayNghi::query()->findOrFail($id)->delete();

        return back()->with('success', 'Đã xoá ngày nghỉ.');
    }

    /** Gán loại khoá cho các mã khoá-lớp của một dòng khoá thực tế; 'mac_dinh' = bỏ gán riêng (về theo hạng). */
    public function storeLoaiKhoaThucTe(Request $request): RedirectResponse
    {
        $veMacDinh = $request->boolean('mac_dinh');
        $validated = $request->validate([
            'ma_khoa_lop' => ['required', 'array', 'min:1'],
            'ma_khoa_lop.*' => ['required', 'string', 'max:20'],
            'ten_loai_khoa' => $veMacDinh
                ? ['nullable']
                : ['required', 'string', Rule::exists('sqlsrv_manhlinh.TienDoDuKienLoaiKhoa', 'TenLoai')],
        ], [
            'ma_khoa_lop.required' => 'Chọn ít nhất một khoá.',
            'ten_loai_khoa.required' => 'Chọn loại khoá.',
            'ten_loai_khoa.exists' => 'Loại khoá phải chọn từ danh sách «Loại khoá dự kiến».',
        ]);

        $maList = array_values(array_unique(array_map(
            fn ($ma) => strtoupper(trim((string) $ma)),
            $validated['ma_khoa_lop']
        )));
        $dsMa = implode(', ', $maList);

        if ($veMacDinh) {
            TienDoThucTeLoaiKhoa::query()->whereIn('MaKhoaLop', $maList)->delete();

            return back()->with('success', "Đã đưa {$dsMa} về loại khoá mặc định theo hạng.");
        }

        $ten = trim((string) $validated['ten_loai_khoa']);
        foreach ($maList as $ma) {
            TienDoThucTeLoaiKhoa::query()->updateOrCreate(
                ['MaKhoaLop' => $ma],
                ['TenLoaiKhoa' => $ten, 'NgayTao' => now()]
            );
        }

        return back()->with('success', "Đã gán loại khoá «{$ten}» cho {$dsMa}.");
    }

    private static function mauMacDinhTheoHang(string $hang): string
    {
        return match ($hang) {
            'B01' => '#FFB3B3',
            'C' => '#FFD966',
            'B' => '#C5E0B4',
            default => '#E0E0E0',
        };
    }

    /** @param  array<string, mixed>  $validated */
    private static function mauLoaiKhoaTuRequest(Request $request, array $validated): string
    {
        $mau = trim((string) ($validated['mau_sac'] ?? $request->input('mau_sac', '')));
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $mau)) {
            return $mau;
        }

        return self::mauMacDinhTheoHang((string) ($validated['hang'] ?? 'B01'));
    }

    /** @return array<string, mixed> */
    private function loaiKhoaTuRequest(Request $request): array
    {
        $validated = $request->validate([
            'ten_loai' => ['required', 'string', 'max:100'],
            'hang' => ['required', Rule::in(TienDoDaoTaoNam::HANG)],
            'so_hoc_vien_toi_da' => ['required', 'integer', 'between:1,1000'],
            'so_ngay_ly_thuyet' => ['required', 'integer', 'between:0,365'],
            'so_ngay_thuc_hanh' => ['required', 'integer', 'between:1,365'],
            'so_hoc_vien_moi_xe' => ['required', 'integer', 'between:1,100'],
            'so_giao_vien_moi_xe' => ['nullable', 'integer', 'in:1'],
            'so_ngay_cach' => ['required', 'integer', 'between:0,60'],
            'ngay_khai_giang' => ['nullable', 'date'],
            'dang_su_dung' => ['nullable', 'boolean'],
            'mau_sac' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'ten_loai.required' => 'Nhập tên loại khoá.',
            'hang.in' => 'Hạng phải là B01, C hoặc B.',
        ]);

        return [
            'TenLoai' => trim((string) $validated['ten_loai']),
            'Hang' => $validated['hang'],
            'SoHocVienToiDa' => (int) $validated['so_hoc_vien_toi_da'],
            'SoNgayLyThuyet' => (int) $validated['so_ngay_ly_thuyet'],
            'SoNgayThucHanh' => (int) $validated['so_ngay_thuc_hanh'],
            'SoHocVienMoiXe' => (int) $validated['so_hoc_vien_moi_xe'],
            'SoGiaoVienMoiXe' => 1,
            'SoNgayCach' => (int) $validated['so_ngay_cach'],
            'NgayKhaiGiang' => $validated['ngay_khai_giang'] ?? null,
            'DangSuDung' => $request->boolean('dang_su_dung'),
            'MauSac' => self::mauLoaiKhoaTuRequest($request, $validated),
            'GhiChu' => trim((string) ($validated['ghi_chu'] ?? '')) ?: null,
        ];
    }
}
