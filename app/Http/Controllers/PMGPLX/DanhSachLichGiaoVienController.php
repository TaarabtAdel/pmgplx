<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DanhSachLichGiaoVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $query = KhoaHocGiaoVien::query();

        $maKH = trim((string) $request->input('ma_kh'));
        if ($maKH !== '') {
            $query->where('MaKH', $maKH);
        }

        if ($maGV = trim((string) $request->input('ma_gv'))) {
            $query->where('MaGV', $maGV);
        }

        if ($from = $request->input('tu_ngay')) {
            $query->where('NgayBD', '>=', $from);
        }

        if ($to = $request->input('den_ngay')) {
            $query->where('NgayKT', '<=', $to.' 23:59:59');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('TrangThai', (int) $request->input('trang_thai'));
        }

        $items = $query
            ->orderByDesc('NgayBD')
            ->orderByDesc('MaLichLV')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        $giaoViens = GiaoVien::query()
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV']);

        $khoaChon = null;
        if ($maKH !== '' && ($kh = $khoaHocs->firstWhere('MaKH', $maKH)) !== null) {
            $khoaChon = [
                'ma_kh' => $kh->MaKH,
                'ten_kh' => trim((string) $kh->TenKH),
                'so_buoi' => $this->lichTheoKhoaQuery($kh->MaKH)->count(),
            ];
        }

        return view('PMGPLX.lich.danh-sach-lich-gv', [
            'items' => $items,
            'khoaChon' => $khoaChon,
            'khoaHocs' => $khoaHocs,
            'giaoViens' => $giaoViens,
            'monMap' => DmMonHoc::query()->pluck('TenMH', 'MaMH'),
            'filters' => [
                'ma_kh' => $request->input('ma_kh', ''),
                'ma_gv' => $request->input('ma_gv', ''),
                'tu_ngay' => $request->input('tu_ngay', ''),
                'den_ngay' => $request->input('den_ngay', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function destroy(Request $request, int $maLichLv): RedirectResponse
    {
        $row = KhoaHocGiaoVien::query()
            ->where('MaLichLV', $maLichLv)
            ->where('IsKhoaHocGiaoVien', 0)
            ->first(['MaLichLV', 'MaKH', 'MaGV', 'TenGV', 'NgayBD']);

        if ($row === null) {
            return $this->backToList($request)->with('error', 'Không tìm thấy buổi lịch giáo viên cần xóa.');
        }

        KhoaHocGiaoVien::query()
            ->where('MaLichLV', $maLichLv)
            ->where('IsKhoaHocGiaoVien', 0)
            ->delete();

        return $this->backToList($request)->with('success', sprintf(
            'Đã xóa buổi lịch GV %s (%s) – %s, khóa %s.',
            trim((string) $row->TenGV),
            trim((string) $row->MaGV),
            optional($row->NgayBD)->format('d/m/Y H:i'),
            trim((string) $row->MaKH)
        ));
    }

    public function destroyTheoKhoa(Request $request): RedirectResponse
    {
        $maKh = trim((string) $request->input('ma_kh'));
        $khoa = $maKh !== '' ? KhoaHoc::query()->where('MaKH', $maKh)->first(['MaKH', 'TenKH']) : null;
        if ($khoa === null) {
            return $this->backToList($request)->with('error', 'Chọn khóa học trước khi xóa toàn bộ lịch giáo viên.');
        }

        $deleted = DB::connection('sqlsrv')->transaction(
            fn (): int => $this->lichTheoKhoaQuery($khoa->MaKH)->delete()
        );

        return $this->backToList($request)->with('success', sprintf(
            'Đã xóa %s buổi lịch giáo viên của khóa %s (%s).',
            number_format($deleted),
            trim((string) $khoa->TenKH),
            $khoa->MaKH
        ));
    }

    /** Chỉ buổi lịch (có ngày) — không đụng dòng gán GV vào khóa (IsKhoaHocGiaoVien = 1). */
    private function lichTheoKhoaQuery(string $maKh)
    {
        return KhoaHocGiaoVien::query()
            ->where('MaKH', $maKh)
            ->where('IsKhoaHocGiaoVien', 0)
            ->whereNotNull('NgayBD');
    }

    private function backToList(Request $request): RedirectResponse
    {
        $query = $request->input('return_query');
        parse_str(is_string($query) ? $query : '', $params);
        unset($params['page']);

        return redirect()->route('pmgplx.lich.gv.index', $params);
    }
}
