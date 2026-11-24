<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\DaoTao\DatKhoaHocSelectOptions;
use App\Support\DaoTao\PhuLuc6DanhSachDatCccd;
use App\Support\DaoTao\PhuLuc6DatBaoCao;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhuLuc6DatCabinExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class DatCabinPhuLuc6Controller extends Controller
{
    public const SLUG = '4-dat-cabin-phu-luc-6';

    private const MAU_DANH_SACH_DAT = 'templates/oto/phu-luc-6-danh-sach-dat-mau.xls';

    private const SESSION_DANH_SACH = 'phu_luc_6_danh_sach';

    public function index(): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $danhSachSan = is_array(session(self::SESSION_DANH_SACH)) && session(self::SESSION_DANH_SACH) !== [];

        return view('QldtOto.dat-cabin-phu-luc-6', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaHocOptions' => DatKhoaHocSelectOptions::fromDatDSPhien(),
            'preview' => session('phu_luc_6_preview'),
            'danhSachSan' => $danhSachSan,
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        try {
            [$validated, , $preview] = $this->xuLyForm($request, batBuocFile: true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('qldtoto.'.MenuQldt::tenRouteTrang(self::SLUG).'.index')
            ->with('phu_luc_6_preview', $preview)
            ->withInput($validated);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            [, $danhSach, $preview] = $this->xuLyForm($request, batBuocFile: false);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $out = sys_get_temp_dir().'/'.uniqid('phu-luc-6-', true).'.xlsx';
        try {
            PhuLuc6DatCabinExcel::taiVe($preview, $out);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return response()->download($out, PhuLuc6DatCabinExcel::tenFile($request->input('ma_khoa_hoc')))->deleteFileAfterSend(true);
    }

    /**
     * @return array{0: array<string, string>, 1: list<array{stt: int, cccd: string, ho_ten: string}>, 2: list<array<string, mixed>>}
     */
    private function xuLyForm(Request $request, bool $batBuocFile): array
    {
        $rules = [
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
            'file_danh_sach' => $batBuocFile
                ? ['required', 'file', 'extensions:xls,xlsx', 'max:10240']
                : ['nullable', 'file', 'extensions:xls,xlsx', 'max:10240'],
        ];
        $validated = $request->validate($rules);

        if ($request->hasFile('file_danh_sach')) {
            $path = $request->file('file_danh_sach')?->getRealPath();
            if (! is_string($path) || $path === '') {
                throw new \InvalidArgumentException('Không đọc được file tải lên.');
            }
            $danhSach = PhuLuc6DanhSachDatCccd::docFile($path);
            $request->session()->put(self::SESSION_DANH_SACH, $danhSach);
        } else {
            $danhSach = $request->session()->get(self::SESSION_DANH_SACH);
            if (! is_array($danhSach) || $danhSach === []) {
                throw new \InvalidArgumentException(
                    $batBuocFile
                        ? 'Chọn file danh sách đạt.'
                        : 'Chưa có danh sách — upload file và bấm «Xem trước», hoặc chọn file mới.',
                );
            }
        }

        $preview = PhuLuc6DatBaoCao::dongDuLieu(
            $danhSach,
            $validated['ma_khoa_hoc'],
            $validated['tu_ngay'] ?? null,
            $validated['den_ngay'] ?? null,
        );

        return [$validated, $danhSach, $preview];
    }

    public function mauDanhSachDat(): BinaryFileResponse|RedirectResponse
    {
        $path = resource_path(self::MAU_DANH_SACH_DAT);
        if (! is_file($path)) {
            return back()->with('error', 'Chưa có file mẫu danh sách đạt trên máy chủ.');
        }

        return response()->download($path, 'mau-danh-sach-dat-thi-ktm.xls');
    }
}