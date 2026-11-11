<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\BienBanPhanCongToChamDocx;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\QuyetDinhToChucKiemTraKetThucMonDocx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class QuyetDinhToChucKiemTraBbPhanCongController extends Controller
{
    private const SLUG = '2-quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon-bien-ban-phan-cong';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        if ($khoa instanceof KhoaHoc) {
            $hang = BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null);
            $form = [
                'ma_kh' => (string) $khoa->MaKH,
                'so_qd' => '',
                'ngay_quyet_dinh' => now()->format('Y-m-d'),
                'ngay_lap' => now()->format('Y-m-d'),
                'hang' => $hang,
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'so_hv' => (string) BaoCaoDangKyKhoaHoc::demHocVien((string) $khoa->MaKH),
                'ghi_thi_lai' => QuyetDinhToChucKiemTraKetThucMonDocx::GHI_THI_LAI_MAC_DINH,
                'gio_1' => '07h',
                'ngay_1' => '',
                'co_lan_2' => true,
                'gio_2' => '07h',
                'ngay_2' => '',
                'dia_diem' => QuyetDinhToChucKiemTraKetThucMonDocx::DIA_DIEM_MAC_DINH,
                'giam_doc' => QuyetDinhToChucKiemTraKetThucMonDocx::GIAM_DOC_MAC_DINH,
                'ngay_kiem_tra' => '',
                'pho_truong_phong' => BienBanPhanCongToChamDocx::PHO_TRUONG_PHONG_MAC_DINH,
                'nv' => array_map(
                    fn ($ten) => ['ten' => $ten, 'nguoi' => [['xung' => 'Ông', 'ten' => '']]],
                    BienBanPhanCongToChamDocx::NHIEM_VU_MAC_DINH
                ),
            ];
        }

        return view('QldtOto.quyet-dinh-to-chuc-ktm-bb-phan-cong', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'giaoVien' => $form === null ? [] : self::giaoVienGoiY(),
            'xungOptions' => BienBanPhanCongToChamDocx::XUNG,
        ]);
    }

    public function exportQuyetDinh(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $qd = $this->payloadQuyetDinh($data);
        $tmp = storage_path('app/tmp/qd-to-chuc-ktm-'.uniqid('', true).'.docx');
        try {
            QuyetDinhToChucKiemTraKetThucMonDocx::generate($qd, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Quyết định thất bại: '.$e->getMessage())->withInput();
        }

        return $this->download($tmp, 'quyet-dinh-to-chuc-ktm-'.$this->safeMa($data['ma_kh']).'.docx');
    }

    public function exportBienBan(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $bb = $this->payloadBienBan($data);
        $tmp = storage_path('app/tmp/bb-phan-cong-ktm-'.uniqid('', true).'.docx');
        try {
            BienBanPhanCongToChamDocx::generate($bb, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Biên bản thất bại: '.$e->getMessage())->withInput();
        }

        return $this->download($tmp, 'bien-ban-phan-cong-to-cham-'.$this->safeMa($data['ma_kh']).'.docx');
    }

    public function exportCaHai(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $this->validateChung($request);
        $qdPath = storage_path('app/tmp/qd-to-chuc-ktm-'.uniqid('', true).'.docx');
        $bbPath = storage_path('app/tmp/bb-phan-cong-ktm-'.uniqid('', true).'.docx');
        $zipPath = storage_path('app/tmp/qd-bb-ktm-'.uniqid('', true).'.zip');

        try {
            QuyetDinhToChucKiemTraKetThucMonDocx::generate($this->payloadQuyetDinh($data), $qdPath);
            BienBanPhanCongToChamDocx::generate($this->payloadBienBan($data), $bbPath);
        } catch (\Throwable $e) {
            @unlink($qdPath);
            @unlink($bbPath);

            return back()->with('error', 'Xuất file thất bại: '.$e->getMessage())->withInput();
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($qdPath);
            @unlink($bbPath);

            return back()->with('error', 'Không tạo được file ZIP.')->withInput();
        }
        $safe = $this->safeMa($data['ma_kh']);
        $zip->addFile($qdPath, 'quyet-dinh-to-chuc-ktm-'.$safe.'.docx');
        $zip->addFile($bbPath, 'bien-ban-phan-cong-to-cham-'.$safe.'.docx');
        $zip->close();
        @unlink($qdPath);
        @unlink($bbPath);

        return $this->download($zipPath, 'qd-bb-ktm-'.$safe.'.zip');
    }

    /** @return array<string, mixed> */
    private function validateChung(Request $request): array
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'ngay_lap' => ['required', 'date'],
            'hang' => ['required', 'string', 'max:40'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'so_hv' => ['required', 'integer', 'min:1'],
            'ghi_thi_lai' => ['nullable', 'string', 'max:300'],
            'gio_1' => ['required', 'string', 'max:20'],
            'ngay_1' => ['required', 'date'],
            'co_lan_2' => ['nullable', 'boolean'],
            'gio_2' => ['nullable', 'string', 'max:20'],
            'ngay_2' => ['nullable', 'date'],
            'dia_diem' => ['required', 'string', 'max:400'],
            'giam_doc' => ['required', 'string', 'max:100'],
            'ngay_kiem_tra' => ['required', 'date'],
            'pho_truong_phong' => ['required', 'string', 'max:100'],
            'nv' => ['required', 'array', 'min:1'],
            'nv.*.ten' => ['required', 'string', 'max:500'],
            'nv.*.nguoi' => ['nullable', 'array'],
            'nv.*.nguoi.*.xung' => ['required', Rule::in(BienBanPhanCongToChamDocx::XUNG)],
            'nv.*.nguoi.*.ten' => ['nullable', 'string', 'max:100'],
        ], [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
            'ngay_kiem_tra.required' => 'Chọn ngày kiểm tra (biên bản).',
            'nv.required' => 'Cần ít nhất một nhiệm vụ.',
            'nv.*.ten.required' => 'Nhập tên nhiệm vụ.',
        ]);

        if (! empty($data['co_lan_2'])) {
            $request->validate([
                'gio_2' => ['required', 'string', 'max:20'],
                'ngay_2' => ['required', 'date'],
            ]);
        }

        $data['co_lan_2'] = ! empty($data['co_lan_2']);
        $data['ghi_thi_lai'] = trim((string) ($data['ghi_thi_lai'] ?? ''));

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{nhiem_vu: list<array{ten: string, nguoi: list<array{xung: string, ten: string}>}>}
     */
    private function parseNhiemVu(array $data): array
    {
        $nhiemVu = [];
        foreach (array_values($data['nv']) as $i => $nv) {
            $nguoi = [];
            foreach ($nv['nguoi'] ?? [] as $ng) {
                $ten = trim((string) ($ng['ten'] ?? ''));
                if ($ten !== '') {
                    $nguoi[] = ['xung' => $ng['xung'], 'ten' => preg_replace('/\s+/u', ' ', $ten)];
                }
            }
            if ($nguoi === []) {
                throw ValidationException::withMessages([
                    'nv' => 'Nhiệm vụ '.($i + 1).' chưa có người thực hiện — thêm người hoặc bỏ nhiệm vụ đó.',
                ]);
            }
            $nhiemVu[] = ['ten' => trim($nv['ten']), 'nguoi' => $nguoi];
        }

        return $nhiemVu;
    }

    /** @param  array<string, mixed>  $data */
    private function payloadQuyetDinh(array $data): array
    {
        return [
            'so_qd' => trim($data['so_qd']),
            'ngay_quyet_dinh' => $data['ngay_quyet_dinh'],
            'so_hv' => $data['so_hv'],
            'hang' => trim($data['hang']),
            'khoa_so' => trim($data['khoa_so']),
            'ghi_thi_lai' => $data['ghi_thi_lai'],
            'gio_1' => trim($data['gio_1']),
            'ngay_1' => $data['ngay_1'],
            'co_lan_2' => $data['co_lan_2'],
            'gio_2' => $data['gio_2'] ?? '',
            'ngay_2' => $data['ngay_2'] ?? '',
            'dia_diem' => trim($data['dia_diem']),
            'giam_doc' => trim($data['giam_doc']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function payloadBienBan(array $data): array
    {
        return [
            'ngay_lap' => $data['ngay_lap'],
            'hang' => trim($data['hang']),
            'khoa_so' => trim($data['khoa_so']),
            'so_qd' => trim($data['so_qd']),
            'ngay_qd' => $data['ngay_quyet_dinh'],
            'ngay_kiem_tra' => $data['ngay_kiem_tra'],
            'pho_truong_phong' => trim($data['pho_truong_phong']),
            'nhiem_vu' => $this->parseNhiemVu($data),
        ];
    }

    private function safeMa(string $maKh): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '-', $maKh) ?: 'khoa';
    }

    private function download(string $tmp, string $name): BinaryFileResponse
    {
        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }

    /** @return list<array{ten: string, xung: string}> */
    private static function giaoVienGoiY(): array
    {
        return GiaoVien::query()
            ->where('TrangThai', 1)
            ->orderBy('TenGV')
            ->orderBy('HoTenDem')
            ->get(['HoTenDem', 'TenGV', 'GioiTinh'])
            ->map(fn (GiaoVien $gv) => [
                'ten' => $gv->ho_ten,
                'xung' => strtoupper(trim((string) $gv->GioiTinh)) === 'F' ? 'Bà' : 'Ông',
            ])
            ->filter(fn ($gv) => $gv['ten'] !== '')
            ->unique('ten')
            ->values()
            ->all();
    }
}
