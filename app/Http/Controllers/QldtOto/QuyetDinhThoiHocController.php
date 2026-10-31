<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\DanhSachHocSinhLaiXeHocVien;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhThoiHocDocx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuyetDinhThoiHocController extends Controller
{
    private const SLUG = '2-quyet-dinh-cho-thoi-hoc';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        if ($khoa instanceof KhoaHoc) {
            $form = QuyetDinhHocVienForm::macDinhChung($khoa);
            $hang = DanhSachHocSinhLaiXeHocVien::hangTieuDe($khoa->HangGPLX ?? null, $khoa->HangDT ?? null);
            $form += [
                'hang' => $hang,
                'lop_tieu_de' => $hang.'-K'.$form['khoa_so'],
                'noi_cu_tru' => '',
                'gvcn' => '',
                'ly_do' => QuyetDinhThoiHocDocx::LY_DO_MAC_DINH,
            ];
        }

        return view('QldtOto.quyet-dinh-hoc-vien', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'loai' => 'thoi_hoc',
            'mau' => 'quyet-dinh-cho-thoi-hoc.docx',
            'routeIndex' => 'qldtoto.quyet-dinh-cho-thoi-hoc.index',
            'routeExport' => 'qldtoto.quyet-dinh-cho-thoi-hoc.export',
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'hocVien' => $khoa instanceof KhoaHoc ? QuyetDinhHocVienForm::hocVien($khoa) : [],
            'giaoVienTen' => $khoa instanceof KhoaHoc
                ? PhanCongGvXeTapLaiFormSupport::giaoVienSelectOptions()
                    ->map(fn (array $gv): string => QuyetDinhDoiGiaoVienSupport::chuanHoaHoTen($gv['ho_ten']))
                    ->filter()->unique()->values()
                : collect(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate(QuyetDinhHocVienForm::rulesChung() + [
            'noi_cu_tru' => ['required', 'string', 'max:200'],
            'lop_tieu_de' => ['required', 'string', 'max:40'],
            'gvcn' => ['required', 'string', 'max:120'],
        ], QuyetDinhHocVienForm::messagesChung() + [
            'gvcn.required' => 'Nhập giáo viên chủ nhiệm (căn cứ báo cáo học viên).',
        ]);

        $data = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $tmp = storage_path('app/tmp/quyet-dinh-cho-thoi-hoc-'.uniqid('', true).'.docx');
        try {
            QuyetDinhThoiHocDocx::generate($data, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $ten = 'quyet-dinh-cho-thoi-hoc-'.$data['so_qd'].'-'.(Str::slug($data['ho_ten']) ?: 'hoc-vien').'.docx';

        return response()->download($tmp, $ten)->deleteFileAfterSend(true);
    }
}
