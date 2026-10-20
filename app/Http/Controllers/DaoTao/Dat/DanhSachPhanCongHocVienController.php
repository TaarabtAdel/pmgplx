<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\XeTap;
use App\Support\DaoTao\DatPhanCongGiaoVienThaySaver;
use App\Support\DaoTao\DatPhanCongHocVienBoLoc;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use App\Support\DaoTao\DatPhanCongTheoKhoaPresenter;
use App\Support\DaoTao\DatPhanCongXeThaySaver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class DanhSachPhanCongHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatPhanCongHocVienBoLoc::parseFilters($request);
        $maKhoaHoc = (string) ($filters['ma_khoa_hoc'] ?? '');
        $hocVienFilters = DatPhanCongTheoKhoaPresenter::parseHocVienFilters($request);

        $khoaHocOptions = DatPhanCongHocVienBoLoc::khoaHocOptions();
        $tenKhoa = '';
        if ($maKhoaHoc !== '') {
            $tenKhoa = (string) (KhoaHoc::query()->where('MaKH', $maKhoaHoc)->value('TenKH') ?? '');
        }

        $phanCongTheoGv = [];
        $hocVienItems = new LengthAwarePaginator([], 0, $hocVienFilters['per_page']);
        $phanCongByRowKey = [];
        $phanCongTrungMaHocVien = [];
        $gvLocOptions = [];

        $giaoViens = GiaoVien::query()
            ->where('TrangThai', 1)
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV', 'GhiChu']);

        $gvBienSoByMa = [];
        foreach ($giaoViens as $gv) {
            $gvBienSoByMa[(string) $gv->MaGV] = trim((string) ($gv->GhiChu ?? ''));
        }

        $xeTaps = XeTap::query()->orderBy('BienSoXe')->pluck('BienSoXe');

        $giaoViensPhanCong = $giaoViens;

        if ($maKhoaHoc !== '') {
            $phanCongTheoGv = DatPhanCongTheoKhoaPresenter::groupsByGiaoVien($maKhoaHoc);
            $hocVienItems = DatPhanCongTheoKhoaPresenter::hocVienQuery(
                $maKhoaHoc,
                $hocVienFilters['tu_khoa'],
                $hocVienFilters['an_da_phan_cong'],
                $hocVienFilters['gv_phan_cong']
            )
                ->paginate($hocVienFilters['per_page'])
                ->withQueryString();
            $phanCongByRowKey = DatPhanCongTheoKhoaPresenter::phanCongByRowKeyForPaginator($hocVienItems);
            $phanCongTrungMaHocVien = DatPhanCongHocVienSaver::duplicateMaHocVienInKhoa($maKhoaHoc);

            if ($phanCongTheoGv !== []) {
                $allowedMaGv = array_column($phanCongTheoGv, 'ma_giao_vien');
                $giaoViensPhanCong = $giaoViens->whereIn('MaGV', $allowedMaGv)->values();
            }

            $hvTheoGv = DatPhanCongTheoKhoaPresenter::hocVienTheoGvHienTai($maKhoaHoc);
            foreach ($phanCongTheoGv as $gvGroup) {
                $maGv = (string) ($gvGroup['ma_giao_vien'] ?? '');
                $soHv = count($hvTheoGv[$maGv] ?? []);
                $gvLocOptions[] = [
                    'ma_giao_vien' => $maGv,
                    'label' => (($gvGroup['ten_giao_vien'] ?? '') ?: $maGv).' ('.$maGv.') — '.$soHv.' HV',
                ];
            }
        }

        return view('DaoTao.dat.phan-cong-hoc-vien', [
            'filters' => $filters,
            'hocVienFilters' => $hocVienFilters,
            'khoaHocOptions' => $khoaHocOptions,
            'khoaHocCounts' => DatPhanCongHocVien::query()
                ->selectRaw('MaKhoaHoc, COUNT(*) as cnt')
                ->groupBy('MaKhoaHoc')
                ->orderBy('MaKhoaHoc')
                ->pluck('cnt', 'MaKhoaHoc'),
            'maKhoaHoc' => $maKhoaHoc,
            'tenKhoa' => $tenKhoa,
            'phanCongTheoGv' => $phanCongTheoGv,
            'hocVienItems' => $hocVienItems,
            'phanCongByRowKey' => $phanCongByRowKey,
            'phanCongTrungMaHocVien' => $phanCongTrungMaHocVien,
            'gvLocOptions' => $gvLocOptions,
            'giaoViens' => $giaoViensPhanCong,
            'gvBienSoByMa' => $gvBienSoByMa,
            'xeTaps' => $xeTaps,
        ]);
    }

    public function themPhanCong(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'hoc_vien' => ['required', 'array', 'min:1'],
            'hoc_vien.*.ma_dk' => ['required', 'string', 'max:50'],
            'hoc_vien.*.ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'hoc_vien.*.ho_ten' => ['nullable', 'string', 'max:255'],
            'ma_giao_vien' => ['required', 'string', 'max:50'],
            'bien_so_xe' => ['nullable', 'string', 'max:50'],
            'bien_so_xe_tu_dong' => ['nullable', 'string', 'max:50'],
            'tu_ngay' => ['nullable', 'date'],
        ], [
            'hoc_vien.required' => 'Chọn ít nhất một học viên.',
            'ma_giao_vien.required' => 'Chọn giáo viên.',
            'tu_ngay.date' => 'Từ ngày không hợp lệ.',
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);
        $maGiaoVien = trim((string) $validated['ma_giao_vien']);
        $bienSoXe = trim((string) ($validated['bien_so_xe'] ?? ''));
        $bienSoXeTuDong = trim((string) ($validated['bien_so_xe_tu_dong'] ?? ''));
        $tuNgay = trim((string) ($validated['tu_ngay'] ?? '')) ?: null;

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($validated['hoc_vien'] as $index => $hv) {
            $line = $index + 1;
            try {
                $result = DatPhanCongHocVienSaver::upsert(
                    trim((string) $hv['ma_khoa_hoc']),
                    trim((string) $hv['ma_dk']),
                    $maGiaoVien,
                    $bienSoXe,
                    trim((string) ($hv['ho_ten'] ?? '')),
                    'Phân công học viên (ĐAT)',
                    null,
                    $bienSoXeTuDong,
                    true,
                    $tuNgay
                );
                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (ValidationException $e) {
                $errors[] = "Dòng {$line} ({$hv['ma_dk']}): ".implode(' ', array_map(
                    static fn (array $msgs): string => (string) ($msgs[0] ?? ''),
                    $e->errors()
                ));
            } catch (Throwable $e) {
                $errors[] = "Dòng {$line} ({$hv['ma_dk']}): ".$e->getMessage();
            }
        }

        $redirect = redirect()->route('daotao.pdt.dat.phan-cong-hoc-vien', array_filter([
            'ma_khoa_hoc' => $maKhoaHoc,
            'tu_khoa' => $request->input('tu_khoa'),
            'per_page' => $request->input('per_page'),
            'an_da_phan_cong' => $request->boolean('an_da_phan_cong') ? '1' : null,
            'gv_phan_cong' => $request->input('gv_phan_cong'),
        ]));

        if ($created + $updated === 0) {
            return $redirect
                ->withInput()
                ->with('error', $errors !== [] ? implode("\n", $errors) : 'Không lưu được phân công nào.');
        }

        $msg = 'Đã ghi phân công: '.number_format($created + $updated).' học viên';
        if ($created > 0) {
            $msg .= ' (mới: '.number_format($created);
            $msg .= $updated > 0 ? ', cập nhật: '.number_format($updated).')' : ')';
        } elseif ($updated > 0) {
            $msg .= ' (cập nhật: '.number_format($updated).')';
        }
        $msg .= '.';

        if ($errors !== []) {
            $msg .= ' Một số dòng lỗi: '.implode(' · ', array_slice($errors, 0, 3));
        }

        return $redirect->with('success', $msg);
    }

    public function destroyByCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
        ], [
            'ma_khoa_hoc.required' => 'Chọn mã khóa học cần xóa.',
        ]);

        $maKhoaHoc = trim((string) $validated['ma_khoa_hoc']);
        $deleted = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'Không có phân công nào cho khóa '.$maKhoaHoc.'.');
        }

        DatPhanCongGiaoVienThaySaver::deleteByKhoaHoc($maKhoaHoc);
        DatPhanCongXeThaySaver::deleteByKhoaHoc($maKhoaHoc);

        return redirect()
            ->route('daotao.pdt.dat.phan-cong-hoc-vien', ['ma_khoa_hoc' => $maKhoaHoc])
            ->with('success', 'Đã xóa '.number_format($deleted).' phân công của khóa '.$maKhoaHoc.'.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $row = DatPhanCongHocVien::query()->find($id);
        if ($row === null) {
            return back()->with('error', 'Không tìm thấy phân công cần xóa.');
        }

        $maKhoa = trim((string) $row->MaKhoaHoc);
        $label = trim((string) ($row->HoTenHocVien ?: $row->MaHocVien));
        if ($row->nhanKhoangNgay() !== '') {
            $label .= ' ('.$row->nhanKhoangNgay().')';
        }
        DatPhanCongHocVienSaver::xoaDong($row);

        return redirect()
            ->route('daotao.pdt.dat.phan-cong-hoc-vien', array_filter(['ma_khoa_hoc' => $maKhoa]))
            ->with('success', 'Đã xóa phân công học viên '.$label.'.');
    }
}
