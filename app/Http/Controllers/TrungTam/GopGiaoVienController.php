<?php

namespace App\Http\Controllers\TrungTam;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\GiaoVien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class GopGiaoVienController extends Controller
{
    public function create(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        $filters = [
            'tu_khoa' => trim((string) ($request->input('tu_khoa') ?? '')),
            'loai_gv' => trim((string) ($request->input('loai_gv') ?? '')),
            'trang_thai' => (string) ($request->input('trang_thai') ?? ''),
            'per_page' => $perPage,
        ];

        $items = GiaoVien::filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return view('TrungTam.giao-vien.gop', [
            'items' => $items,
            'filters' => $filters,
            'loaiGvOptions' => ['GVLT', 'GVTH'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keep_id' => ['required', 'integer', 'min:1'],
            'merge_ids' => ['required', 'array', 'min:1'],
            'merge_ids.*' => ['integer', 'min:1'],
        ], [
            'keep_id.required' => 'Chọn giáo viên sẽ giữ lại.',
            'merge_ids.required' => 'Chọn ít nhất một giáo viên cần gộp.',
            'merge_ids.min' => 'Chọn ít nhất một giáo viên cần gộp.',
        ]);

        $keepId = (int) $validated['keep_id'];
        $mergeIds = array_map('intval', $validated['merge_ids']);

        if (in_array($keepId, $mergeIds, true)) {
            return back()
                ->withInput()
                ->with('error', 'Giáo viên giữ lại không được nằm trong danh sách gộp.');
        }

        try {
            $result = GiaoVien::mergeRecords($keepId, $mergeIds);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gộp giáo viên thất bại: '.$e->getMessage());
        }

        if (! ($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->with('error', $result['error'] ?? 'Không thể gộp giáo viên.');
        }

        $keep = GiaoVien::query()->find($keepId);

        return redirect()
            ->route('trungtam.giao-vien.danh-sach', ['tu_khoa' => $keep?->HoTen ?? ''])
            ->with('success', sprintf(
                'Đã gộp %d giáo viên vào "%s". Cập nhật %d dòng phân công.',
                $result['merged_count'],
                $keep?->HoTen ?? ('ID '.$keepId),
                $result['phan_cong_updated']
            ));
    }
}
