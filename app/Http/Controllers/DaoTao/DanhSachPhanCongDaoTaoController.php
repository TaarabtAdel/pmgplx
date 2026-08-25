<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\GiaoVien;
use App\Models\DaoTao\KhoaDaoTao;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Models\DaoTao\XeTapLai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DanhSachPhanCongDaoTaoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('ten_khoa') && ! $request->filled('khoa_dao_tao_id')) {
            $khoaId = KhoaDaoTao::query()
                ->where('TenKhoa', KhoaDaoTao::normalizeTenKhoa((string) $request->input('ten_khoa')))
                ->value('Id');

            if ($khoaId !== null) {
                return redirect()->route('daotao.pdt.phan-cong-dao-tao.danh-sach', array_merge(
                    $request->except(['ten_khoa']),
                    ['khoa_dao_tao_id' => $khoaId]
                ));
            }
        }

        $filters = [
            'khoa_dao_tao_id' => $this->positiveIntOrNull($request->input('khoa_dao_tao_id')),
            'giao_vien_id' => $this->positiveIntOrNull($request->input('giao_vien_id')),
            'xe_tap_lai_id' => $this->positiveIntOrNull($request->input('xe_tap_lai_id')),
        ];

        $checkGiaoVienAll = $request->input('check_gv') === 'all';
        $checkXeAll = $request->input('check_xe') === 'all';

        $query = PhanCongDaoTao::query()->with(['giaoVien', 'xeTapLai', 'khoaDaoTao']);

        if ($filters['khoa_dao_tao_id'] !== null) {
            $query->where('KhoaDaoTaoId', $filters['khoa_dao_tao_id']);
        }

        if ($filters['giao_vien_id'] !== null) {
            $query->where('GiaoVienId', $filters['giao_vien_id']);
        }

        if ($filters['xe_tap_lai_id'] !== null) {
            $query->where('XeTapLaiId', $filters['xe_tap_lai_id']);
        }

        $items = $query
            ->orderBy('TuNgay')
            ->paginate(50)
            ->withQueryString();

        $selectedGiaoVien = $filters['giao_vien_id'] !== null
            ? GiaoVien::query()->find($filters['giao_vien_id'])
            : null;

        $selectedXeTapLai = $filters['xe_tap_lai_id'] !== null
            ? XeTapLai::query()->find($filters['xe_tap_lai_id'])
            : null;

        $giaoVienOverlapWarnings = [];
        $giaoVienScheduleChecked = false;
        $giaoVienCheckScope = '';

        if ($checkGiaoVienAll) {
            $gvIds = PhanCongDaoTao::query()
                ->whereNotNull('GiaoVienId')
                ->distinct()
                ->pluck('GiaoVienId')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $giaoVienOverlapWarnings = PhanCongDaoTao::crossKhoaOverlapWarningsForGiaoVienIds($gvIds);
            $giaoVienScheduleChecked = true;
            $giaoVienCheckScope = 'tất cả giáo viên ('.number_format(count($gvIds)).' GV có phân công)';
        } elseif ($selectedGiaoVien !== null) {
            $giaoVienOverlapWarnings = PhanCongDaoTao::crossKhoaOverlapWarningsForGiaoVienIds([$filters['giao_vien_id']]);
            $giaoVienScheduleChecked = true;
            $giaoVienCheckScope = (string) $selectedGiaoVien->HoTen;
        }

        $xeOverlapWarnings = [];
        $xeScheduleChecked = false;
        $xeCheckScope = '';

        if ($checkXeAll) {
            $xeIds = PhanCongDaoTao::query()
                ->whereNotNull('XeTapLaiId')
                ->distinct()
                ->pluck('XeTapLaiId')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $xeOverlapWarnings = PhanCongDaoTao::crossKhoaOverlapWarningsForXeTapLaiIds($xeIds);
            $xeScheduleChecked = true;
            $xeCheckScope = 'tất cả xe ('.number_format(count($xeIds)).' xe có phân công)';
        } elseif ($selectedXeTapLai !== null) {
            $xeOverlapWarnings = PhanCongDaoTao::crossKhoaOverlapWarningsForXeTapLaiIds([$filters['xe_tap_lai_id']]);
            $xeScheduleChecked = true;
            $xeCheckScope = (string) $selectedXeTapLai->BienSo;
        }

        $luuLuongBackParams = $this->luuLuongBackParams($request);
        $backToLuuLuongUrl = $luuLuongBackParams !== []
            ? route('daotao.pdt.bc.luu-luong-dao-tao', array_diff_key($luuLuongBackParams, ['from' => true]))
            : null;

        $listQueryParams = array_filter(array_merge(
            $filters,
            $luuLuongBackParams,
            array_filter([
                'check_gv' => $checkGiaoVienAll ? 'all' : null,
                'check_xe' => $checkXeAll ? 'all' : null,
            ])
        ), fn ($value) => $value !== null && $value !== '');

        return view('DaoTao.phan-cong-dao-tao.danh-sach', [
            'filters' => $filters,
            'items' => $items,
            'khoaOptions' => KhoaDaoTao::query()->orderBy('TenKhoa')->get(['Id', 'TenKhoa']),
            'giaoVienOptions' => GiaoVien::allOrderedByVietnameseName(),
            'xeOptions' => XeTapLai::query()->orderBy('BienSo')->get(['Id', 'BienSo']),
            'selectedGiaoVien' => $selectedGiaoVien,
            'selectedXeTapLai' => $selectedXeTapLai,
            'giaoVienOverlapWarnings' => $giaoVienOverlapWarnings,
            'xeOverlapWarnings' => $xeOverlapWarnings,
            'giaoVienScheduleChecked' => $giaoVienScheduleChecked,
            'xeScheduleChecked' => $xeScheduleChecked,
            'giaoVienCheckScope' => $giaoVienCheckScope,
            'xeCheckScope' => $xeCheckScope,
            'checkGiaoVienAll' => $checkGiaoVienAll,
            'checkXeAll' => $checkXeAll,
            'listQueryParams' => $listQueryParams,
            'backToLuuLuongUrl' => $backToLuuLuongUrl,
            'luuLuongBackParams' => $luuLuongBackParams,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function luuLuongBackParams(Request $request): array
    {
        if ($request->input('from') !== 'luu-luong-dao-tao') {
            return [];
        }

        $params = ['from' => 'luu-luong-dao-tao'];

        $ngayKiemTra = trim((string) ($request->input('ngay_kiem_tra') ?? ''));
        if ($ngayKiemTra !== '') {
            $params['ngay_kiem_tra'] = $ngayKiemTra;
        }

        $kyHieu = trim((string) ($request->input('ky_hieu') ?? ''));
        if ($kyHieu !== '') {
            $params['ky_hieu'] = $kyHieu;
        }

        $hangGplx = trim((string) ($request->input('hang_gplx') ?? ''));
        if ($hangGplx !== '') {
            $params['hang_gplx'] = $hangGplx;
        }

        return $params;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
