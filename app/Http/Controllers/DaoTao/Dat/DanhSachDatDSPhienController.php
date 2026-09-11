<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Models\DaoTao\DatPhanLoaiPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatDSPhienExcelExporter;
use App\Support\DaoTao\DatDSPhienKiemTra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DanhSachDatDSPhienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);
        $canAnalyzeViolations = ($filters['ma_khoa_hoc'] ?? '') !== '';
        $resolved = $this->resolveFiltered($filters, $canAnalyzeViolations);

        $items = (clone $resolved['query'])->with('phanLoai')->paginate(50)->withQueryString();

        $loiCounts = null;
        if ($canAnalyzeViolations) {
            $loiCounts = [];
            foreach (DatDSPhienKiemTra::definitions() as $code => $definition) {
                $loiCounts[$code] = 0;
            }
            foreach ($resolved['violationsById'] as $codes) {
                foreach ($codes as $code) {
                    if (isset($loiCounts[$code])) {
                        $loiCounts[$code]++;
                    }
                }
            }
        }

        $phanLoais = DatPhanLoaiPhien::query()
            ->orderBy('ThuTu')
            ->orderBy('TenPhanLoai')
            ->get(['Id', 'TenPhanLoai']);

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $giaoVienOptions = DatDSPhien::query()
            ->selectRaw('MaGiaoVien, MAX(HoTenGiaoVien) as HoTenGiaoVien')
            ->whereNotNull('MaGiaoVien')
            ->where('MaGiaoVien', '!=', '')
            ->groupBy('MaGiaoVien')
            ->orderBy('MaGiaoVien')
            ->get();

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if ($filters['ma_hoc_vien'] !== '') {
            [$maHv, $maKh] = DatDSPhienBoLoc::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $selectedQuery = DatDSPhien::query()
                ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
                ->where('MaHocVien', $maHv)
                ->groupBy('MaHocVien', 'MaKhoaHoc');

            if ($maKh !== '') {
                $selectedQuery->where('MaKhoaHoc', $maKh);
            }

            $selectedRow = $selectedQuery->first();
            if ($selectedRow) {
                $selectedHocVienOption = $this->hocVienOptionFromRow($selectedRow);
            }
        }

        return view('DaoTao.dat.danh-sach', [
            'items' => $items,
            'filters' => $filters,
            'violationsById' => $resolved['violationsById'],
            'expectedPhanCongById' => $resolved['expectedPhanCongById'],
            'loiDefinitions' => DatDSPhienKiemTra::definitions(),
            'loiCounts' => $loiCounts,
            'canAnalyzeViolations' => $canAnalyzeViolations,
            'tongPhienLoc' => $resolved['filteredCount'],
            'phanLoais' => $phanLoais,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienOptions' => $giaoVienOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
        ]);
    }

    public function timHocVienOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        $query = DatDSPhien::query()
            ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaHocVien')
            ->where('MaHocVien', '!=', '');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $sub) use ($like): void {
                $sub->where('MaHocVien', 'like', $like)
                    ->orWhere('HoTenHocVien', 'like', $like)
                    ->orWhere('MaKhoaHoc', 'like', $like)
                    ->orWhere('TenKhoaHoc', 'like', $like);
            });
        }

        $query->groupBy('MaHocVien', 'MaKhoaHoc')
            ->orderBy('MaHocVien')
            ->orderBy('MaKhoaHoc');

        $offset = ($page - 1) * $perPage;
        $rows = (clone $query)->skip($offset)->take($perPage + 1)->get();
        $more = $rows->count() > $perPage;

        $results = $rows->take($perPage)
            ->map(fn ($row): array => $this->hocVienOptionFromRow($row))
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);
        $canAnalyzeViolations = ($filters['ma_khoa_hoc'] ?? '') !== '';
        $resolved = $this->resolveFiltered($filters, $canAnalyzeViolations);
        $items = (clone $resolved['query'])->with('phanLoai')->get();

        return DatDSPhienExcelExporter::download(
            $items,
            $resolved['violationsById'],
            DatDSPhienKiemTra::definitions(),
            $resolved['expectedPhanCongById']
        );
    }

    public function ganPhanLoai(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phien_ids' => ['required', 'array', 'min:1'],
            'phien_ids.*' => ['integer'],
            'phan_loai_ids' => ['required', 'array', 'min:1'],
            'phan_loai_ids.*' => ['integer'],
            'che_do' => ['required', 'in:gan,go,thay_the'],
        ], [
            'phien_ids.required' => 'Chọn ít nhất một phiên.',
            'phan_loai_ids.required' => 'Chọn ít nhất một phân loại.',
        ]);

        $phienIds = array_values(array_unique(array_map('intval', $validated['phien_ids'])));
        $phanLoaiIds = array_values(array_unique(array_map('intval', $validated['phan_loai_ids'])));
        $cheDo = (string) $validated['che_do'];

        $validPhienCount = DatDSPhien::query()->whereIn('Id', $phienIds)->count();
        if ($validPhienCount !== count($phienIds)) {
            return back()->with('error', 'Một số phiên đã chọn không còn tồn tại.');
        }

        $validLoaiCount = DatPhanLoaiPhien::query()->whereIn('Id', $phanLoaiIds)->count();
        if ($validLoaiCount !== count($phanLoaiIds)) {
            return back()->with('error', 'Một số phân loại không hợp lệ.');
        }

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($phienIds, $phanLoaiIds, $cheDo): void {
            foreach ($phienIds as $phienId) {
                $phien = DatDSPhien::query()->find($phienId);
                if (! $phien) {
                    continue;
                }

                if ($cheDo === 'thay_the') {
                    $phien->phanLoai()->sync($phanLoaiIds);
                } elseif ($cheDo === 'go') {
                    $phien->phanLoai()->detach($phanLoaiIds);
                } else {
                    $phien->phanLoai()->syncWithoutDetaching($phanLoaiIds);
                }
            }
        });

        $message = match ($cheDo) {
            'go' => 'Đã bỏ gán phân loại cho '.count($phienIds).' phiên.',
            'thay_the' => 'Đã thay thế phân loại cho '.count($phienIds).' phiên.',
            default => 'Đã gán phân loại cho '.count($phienIds).' phiên.',
        };

        return back()->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     query: Builder,
     *     violationsById: array<int, list<string>>,
     *     expectedPhanCongById: array<int, array{ma_giao_vien: string, bien_so_xe: string}>,
     *     filteredCount: int|null
     * }
     */
    private function resolveFiltered(array $filters, bool $canAnalyzeViolations): array
    {
        $query = DatDSPhienBoLoc::filteredQuery($filters);

        if (! $canAnalyzeViolations) {
            return [
                'query' => $query,
                'violationsById' => [],
                'expectedPhanCongById' => [],
                'filteredCount' => null,
            ];
        }

        $validationSessions = (clone $query)->get();
        $expectedPhanCongById = [];
        $violationsById = DatDSPhienKiemTra::analyze($validationSessions, $expectedPhanCongById);
        $matchingIds = $this->matchingIds($validationSessions, $violationsById, $filters, $canAnalyzeViolations);

        if ($matchingIds !== null) {
            if ($matchingIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('Id', $matchingIds);
            }
        }

        return [
            'query' => $query,
            'violationsById' => $violationsById,
            'expectedPhanCongById' => $expectedPhanCongById,
            'filteredCount' => $matchingIds === null ? $validationSessions->count() : count($matchingIds),
        ];
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  array<int, list<string>>  $violationsById
     * @param  array<string, mixed>  $filters
     * @return list<int>|null  null = không lọc thêm sau DB
     */
    private function matchingIds(Collection $sessions, array $violationsById, array $filters, bool $canAnalyzeViolations): ?array
    {
        if (! $canAnalyzeViolations) {
            return null;
        }

        if ($filters['loi'] === [] && $filters['dat'] === '') {
            return null;
        }

        $ids = [];

        foreach ($sessions as $session) {
            $id = (int) $session->Id;

            if ($filters['dat'] !== '' && ! DatDSPhienKiemTra::matchesDatFilter($violationsById, $id, $filters['dat'])) {
                continue;
            }

            if ($filters['loi'] !== [] && ! DatDSPhienKiemTra::matchesFilter($violationsById, $id, $filters['loi'])) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @return array{id: string, text: string}
     */
    private function hocVienOptionFromRow(object $row): array
    {
        $maHv = (string) ($row->MaHocVien ?? '');
        $maKh = trim((string) ($row->MaKhoaHoc ?? ''));

        return [
            'id' => DatDSPhienBoLoc::composeMaHocVienValue($maHv, $maKh),
            'text' => $this->formatHocVienOptionText(
                trim((string) ($row->HoTenHocVien ?? '')),
                $maHv,
                trim((string) ($row->TenKhoaHoc ?? '')),
                $maKh
            ),
        ];
    }

    private function formatHocVienOptionText(string $hoTen, string $maHocVien, string $tenKhoaHoc, string $maKhoaHoc): string
    {
        $label = $hoTen !== '' ? $hoTen.' ('.$maHocVien.')' : $maHocVien;
        $khoa = $tenKhoaHoc !== '' ? $tenKhoaHoc : $maKhoaHoc;

        if ($khoa !== '') {
            $label .= ' — '.$khoa;
        }

        return $label;
    }
}
