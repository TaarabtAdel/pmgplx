<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Models\PMGPLX\NguoiLXHoSo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DanhSachHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $items = $this->filteredQuery($request)
            ->orderByDesc('n.NgayTao')
            ->orderBy('n.MaDK')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        return view('PMGPLX.danh-muc.hoc-vien', [
            'items' => $items,
            'khoaHocs' => $khoaHocs,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'ma_kh' => $request->input('ma_kh', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Đồng bộ toàn bộ kết quả theo form tìm kiếm (không phân trang) sang DB bản cũ.
     */
    public function dongBo(Request $request): RedirectResponse
    {
        $maDKs = $this->filteredQuery($request)
            ->orderBy('n.MaDK')
            ->pluck('n.MaDK')
            ->unique()
            ->values()
            ->all();

        if ($maDKs === []) {
            return back()->with('error', 'Không có học viên nào theo bộ lọc để đồng bộ.');
        }

        $colsNguoi = array_values(array_intersect(
            Schema::connection('sqlsrv')->getColumnListing('NguoiLX'),
            Schema::connection('sqlsrv_old')->getColumnListing('NguoiLX')
        ));
        $colsHoSo = array_values(array_intersect(
            Schema::connection('sqlsrv')->getColumnListing('NguoiLX_HoSo'),
            Schema::connection('sqlsrv_old')->getColumnListing('NguoiLX_HoSo')
        ));

        $inserted = 0;
        $updated = 0;
        $failed = 0;

        $nguoiRows = NguoiLX::query()->whereIn('MaDK', $maDKs)->get()->keyBy('MaDK');
        $hoSoRows = NguoiLXHoSo::query()->whereIn('MaDK', $maDKs)->get()->keyBy('MaDK');

        $oldDb = DB::connection('sqlsrv_old');

        try {
            $oldDb->beginTransaction();

            foreach ($maDKs as $maDK) {
                $nguoi = $nguoiRows->get($maDK);
                if (! $nguoi) {
                    $failed++;
                    continue;
                }

                $payloadNguoi = $this->onlyColumns($nguoi->getAttributes(), $colsNguoi);
                $exists = $oldDb->table('NguoiLX')->where('MaDK', $maDK)->exists();
                $oldDb->table('NguoiLX')->updateOrInsert(['MaDK' => $maDK], $payloadNguoi);
                $exists ? $updated++ : $inserted++;

                $hoSo = $hoSoRows->get($maDK);
                if ($hoSo) {
                    $payloadHoSo = $this->onlyColumns($hoSo->getAttributes(), $colsHoSo);
                    $oldDb->table('NguoiLX_HoSo')->updateOrInsert(['MaDK' => $maDK], $payloadHoSo);
                }
            }

            $oldDb->commit();
        } catch (\Throwable $e) {
            $oldDb->rollBack();

            return back()->with('error', 'Đồng bộ thất bại: '.$e->getMessage());
        }

        $total = count($maDKs);

        return back()->with(
            'success',
            "Đã đồng bộ {$total} học viên sang bản cũ (thêm mới: {$inserted}, cập nhật: {$updated}".
            ($failed > 0 ? ", lỗi: {$failed}" : '').').'
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->select([
                'n.MaDK',
                'n.HoDemNLX',
                'n.TenNLX',
                'n.HoVaTen',
                'n.NgaySinh',
                'n.GioiTinh',
                'n.SoCMT',
                'n.TrangThai',
                'h.SoHoSo',
                'h.MaKhoaHoc',
                'h.HangGPLX',
                'h.HangDaoTao',
            ]);

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('n.MaDK', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoVaTen', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoDemNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.TenNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.SoCMT', 'like', '%'.$keyword.'%')
                    ->orWhere('h.SoHoSo', 'like', '%'.$keyword.'%');
            });
        }

        if ($maKH = trim((string) $request->input('ma_kh'))) {
            $query->where('h.MaKhoaHoc', $maKH);
        }

        if ($hang = trim((string) $request->input('hang_gplx'))) {
            $query->where('h.HangGPLX', 'like', '%'.$hang.'%');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('n.TrangThai', (string) (int) $request->input('trang_thai'));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function onlyColumns(array $attributes, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (! array_key_exists($col, $attributes)) {
                continue;
            }
            $val = $attributes[$col];
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }
            $out[$col] = $val;
        }

        return $out;
    }
}
