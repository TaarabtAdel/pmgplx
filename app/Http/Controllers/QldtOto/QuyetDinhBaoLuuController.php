<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\QuyetDinhBaoLuuDocx;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuyetDinhBaoLuuController extends Controller
{
    private const SLUG = '2-quyet-dinh-bao-luu';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        if ($khoa instanceof KhoaHoc) {
            $form = QuyetDinhHocVienForm::macDinhChung($khoa) + [
                'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'ten_lop' => QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa),
                'ly_do' => QuyetDinhBaoLuuDocx::LY_DO_MAC_DINH,
            ];
        }

        return view('QldtOto.quyet-dinh-hoc-vien', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'loai' => 'bao_luu',
            'mau' => 'quyet-dinh-bao-luu.docx',
            'routeIndex' => 'qldtoto.quyet-dinh-bao-luu.index',
            'routeExport' => 'qldtoto.quyet-dinh-bao-luu.export',
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'hocVien' => $khoa instanceof KhoaHoc ? QuyetDinhHocVienForm::hocVien($khoa) : [],
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate(QuyetDinhHocVienForm::rulesChung() + [
            'ten_lop' => ['required', 'string', 'max:40'],
        ], QuyetDinhHocVienForm::messagesChung());

        $data = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $tmp = storage_path('app/tmp/quyet-dinh-bao-luu-'.uniqid('', true).'.docx');
        try {
            QuyetDinhBaoLuuDocx::generate($data, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $ten = 'quyet-dinh-bao-luu-'.$data['so_qd'].'-'.(Str::slug($data['ho_ten']) ?: 'hoc-vien').'.docx';

        return response()->download($tmp, $ten)->deleteFileAfterSend(true);
    }
}
