<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\PhanCongDaoTao;
use App\Support\DaoTao\SoPhanCongDaoTaoExcelParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class NhapFileSoPhanCongGiaoVienController extends Controller
{
    private const SESSION_KEY = 'daotao.preview.so_phan_cong_nhap_file';

    public function create(): View
    {
        return view('DaoTao.phan-cong-dao-tao.nhap-file');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel (.xls, .xlsx).',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $preview = (new SoPhanCongDaoTaoExcelParser())->parse($spreadsheet, $file->getClientOriginalName());
            $saveable = PhanCongDaoTao::saveableRecords($preview['records']);
            $preview['meta']['save_count'] = count($saveable);
            $preview['meta']['skip_count'] = count($preview['records']) - count($saveable);
            $preview['validation_errors'] = PhanCongDaoTao::collectRowErrors($preview['records']);
            $preview['overlap_errors'] = $preview['validation_errors'] === []
                ? PhanCongDaoTao::validateOverlaps($preview['records'])
                : [];

            $request->session()->put(self::SESSION_KEY, $preview);

            return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview');
        } catch (Throwable $e) {
            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['records'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        return view('DaoTao.phan-cong-dao-tao.xem-truoc-nhap-file', [
            'preview' => $preview,
            'saveableCount' => (int) ($preview['meta']['save_count'] ?? count(PhanCongDaoTao::saveableRecords($preview['records']))),
            'canConfirm' => ($preview['validation_errors'] ?? []) === []
                && count(PhanCongDaoTao::saveableRecords($preview['records'])) > 0,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['records'])) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng chọn file Excel.');
        }

        if (($preview['validation_errors'] ?? []) !== []) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview')
                ->with('error', 'Dữ liệu còn lỗi, không thể lưu.');
        }

        if (PhanCongDaoTao::saveableRecords($preview['records']) === []) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview')
                ->with('error', 'Không có dòng nào đủ điều kiện lưu.');
        }

        try {
            $result = PhanCongDaoTao::importFromPreview($preview);
        } catch (Throwable $e) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview')
                ->with('error', 'Lưu DB thất bại: '.$e->getMessage());
        }

        if ($result['errors'] !== []) {
            return redirect()
                ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.preview')
                ->with('error', implode(' ', $result['errors']));
        }

        $request->session()->forget(self::SESSION_KEY);

        $msg = "Đã cập nhật {$result['saved']} dòng phân công ({$result['khoa_count']} khoá)";
        if ($result['updated'] > 0 || $result['created'] > 0) {
            $parts = [];
            if ($result['updated'] > 0) {
                $parts[] = "{$result['updated']} dòng cập nhật";
            }
            if ($result['created'] > 0) {
                $parts[] = "{$result['created']} dòng mới";
            }
            $msg .= ' — '.implode(', ', $parts);
        }
        $msg .= '.';
        if ($result['gv_created'] > 0) {
            $msg .= " Thêm {$result['gv_created']} giáo viên mới.";
        }
        if ($result['xe_created'] > 0) {
            $msg .= " Thêm {$result['xe_created']} xe mới.";
        }

        return redirect()
            ->route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien')
            ->with('success', $msg);
    }
}
