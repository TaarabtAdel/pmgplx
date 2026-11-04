<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Support\MenuQldt;
use App\Support\PMGPLX\NgayVn;
use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\BaoCaoDeNghiSatHachData;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use App\Support\QldtOto\QuyetDinhHocVienForm;
use App\Support\QldtOto\ThongBaoKiemTraKetThucMonDocx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThongBaoKiemTraKetThucMonController extends Controller
{
    private const SLUG = '2-thong-bao-thoi-gian-kiem-tra-ket-thuc-mon';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $form = null;
        $hocVien = [];
        if ($khoa instanceof KhoaHoc) {
            $hocVien = array_map(
                fn ($hv) => ['ma_dk' => $hv['ma_dk'], 'ho_ten' => self::hoTenThuong($hv['ho_ten'])],
                BaoCaoDeNghiSatHachData::hocVien((string) $khoa->MaKH)
            );
            $form = [
                'ma_kh' => (string) $khoa->MaKH,
                'so_tb' => '',
                'ngay_thong_bao' => now()->format('Y-m-d'),
                'hang' => BaoCaoDangKyKhoaHoc::nhanHangGplxQuyetDinh($khoa->HangGPLX ?? null),
                'khoa_so' => BaoCaoDangKyKhoaHoc::soKhoaTuTen($khoa->TenKH) ?? '',
                'ten_lop' => QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa),
                'buoi_1' => ThongBaoKiemTraKetThucMonDocx::BUOI[0],
                'gio_1' => '7h',
                'ngay_1' => '',
                'co_lan_2' => false,
                'buoi_2' => ThongBaoKiemTraKetThucMonDocx::BUOI[0],
                'gio_2' => '7h',
                'ngay_2' => '',
                'dia_diem' => ThongBaoKiemTraKetThucMonDocx::DIA_DIEM_MAC_DINH,
                'tru' => [],
                'ly_do_tru' => ThongBaoKiemTraKetThucMonDocx::LY_DO_TRU_MAC_DINH,
                'thi_lai' => [],
            ];
        }

        return view('QldtOto.thong-bao-kiem-tra-ket-thuc-mon', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => QuyetDinhHocVienForm::khoaOptions(),
            'maKh' => $maKh,
            'form' => $form,
            'hocVien' => $hocVien,
            'buoiOptions' => ThongBaoKiemTraKetThucMonDocx::BUOI,
            'noiDungOptions' => ThongBaoKiemTraKetThucMonDocx::NOI_DUNG_KIEM_TRA,
        ]);
    }

    /** Tìm học viên PMGPLX (mọi khoá) theo tên hoặc số định danh — để thêm vào bảng thi lại. */
    public function timHocVien(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }
        $boKhoa = trim((string) $request->query('bo_khoa', ''));

        $rows = NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->leftJoin('KhoaHoc as k', 'k.MaKH', '=', 'h.MaKhoaHoc')
            ->when($boKhoa !== '', fn ($w) => $w->where('h.MaKhoaHoc', '<>', $boKhoa))
            ->where(function ($w) use ($q): void {
                $w->whereRaw('n.HoVaTen COLLATE Latin1_General_CI_AI LIKE ?', ['%'.$q.'%'])
                    ->orWhere('n.SoCMT', 'like', $q.'%');
            })
            ->orderByDesc('k.NgayKG')
            ->orderBy('n.TenNLX')
            ->limit(30)
            ->get(['n.MaDK', 'n.HoVaTen', 'n.HoDemNLX', 'n.TenNLX', 'n.NgaySinh', 'n.SoCMT', 'h.HangGPLX', 'k.TenKH', 'k.MaKH']);

        return response()->json([
            'results' => $rows->map(function ($n) {
                $khoa = (new KhoaHoc)->setRawAttributes(['TenKH' => $n->TenKH, 'MaKH' => $n->MaKH]);
                $hv = [
                    'ho_ten' => mb_strtoupper($n->ho_ten),
                    'dinh_danh' => trim((string) $n->SoCMT),
                    'ngay_sinh' => NgayVn::format($n->NgaySinh ?? null, 'yyyymmdd'),
                    'hang' => self::hangNgan($n->HangGPLX),
                    'khoa' => $n->MaKH ? QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa) : '',
                ];

                return [
                    'id' => (string) $n->MaDK,
                    'text' => $hv['ho_ten'].' — '.$hv['dinh_danh'].' — '.$hv['ngay_sinh'].($hv['khoa'] !== '' ? ' ('.$hv['khoa'].')' : ''),
                    'hv' => $hv,
                ];
            })->values(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'so_tb' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_thong_bao' => ['required', 'date'],
            'hang' => ['required', 'string', 'max:40'],
            'khoa_so' => ['required', 'string', 'max:20'],
            'ten_lop' => ['required', 'string', 'max:40'],
            'buoi_1' => ['required', Rule::in(ThongBaoKiemTraKetThucMonDocx::BUOI)],
            'gio_1' => ['required', 'string', 'max:20'],
            'ngay_1' => ['required', 'date'],
            'co_lan_2' => ['nullable', 'boolean'],
            'buoi_2' => ['required_if:co_lan_2,1', 'nullable', Rule::in(ThongBaoKiemTraKetThucMonDocx::BUOI)],
            'gio_2' => ['required_if:co_lan_2,1', 'nullable', 'string', 'max:20'],
            'ngay_2' => ['required_if:co_lan_2,1', 'nullable', 'date', 'after_or_equal:ngay_1'],
            'dia_diem' => ['required', 'string', 'max:300'],
            'tru' => ['nullable', 'array'],
            'tru.*' => ['string', 'max:100'],
            'ly_do_tru' => ['nullable', 'string', 'max:300'],
            'thi_lai' => ['nullable', 'array'],
            'thi_lai.*.ho_ten' => ['nullable', 'string', 'max:100'],
            'thi_lai.*.dinh_danh' => ['nullable', 'string', 'max:20'],
            'thi_lai.*.ngay_sinh' => ['nullable', 'string', 'max:20'],
            'thi_lai.*.hang' => ['nullable', 'string', 'max:10'],
            'thi_lai.*.noi_dung' => ['nullable', 'string', 'max:40'],
        ], [
            'so_tb.regex' => 'Số thông báo chỉ gồm chữ số.',
            'ngay_1.required' => 'Chọn ngày kiểm tra lần 1.',
            'ngay_2.required_if' => 'Chọn ngày kiểm tra lần 2.',
            'ngay_2.after_or_equal' => 'Ngày kiểm tra lần 2 phải sau lần 1.',
        ]);

        $thiLai = [];
        foreach ($data['thi_lai'] ?? [] as $r) {
            $r = array_map(fn ($v) => trim((string) $v), $r + array_fill_keys(['ho_ten', 'dinh_danh', 'ngay_sinh', 'hang', 'noi_dung'], ''));
            if ($r['ho_ten'] === '') {
                continue;
            }
            $thiLai[] = [
                'ho_ten' => mb_strtoupper($r['ho_ten']),
                'dinh_danh' => $r['dinh_danh'],
                'ngay_sinh' => $r['ngay_sinh'],
                'hang' => $r['hang'],
                'noi_dung' => $r['noi_dung'],
            ];
        }

        $payload = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        $payload['co_lan_2'] = (bool) ($data['co_lan_2'] ?? false);
        $payload['tru_ho_ten'] = array_values(array_filter(array_map('trim', $data['tru'] ?? [])));
        $payload['ly_do_tru'] = trim((string) ($data['ly_do_tru'] ?? ''));
        $payload['thi_lai'] = $thiLai;

        $tmp = storage_path('app/tmp/thong-bao-kiem-tra-ktm-'.uniqid('', true).'.docx');
        try {
            ThongBaoKiemTraKetThucMonDocx::generate($payload, $tmp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Xuất Word thất bại: '.$e->getMessage())->withInput();
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $data['ma_kh']) ?: 'khoa';

        return response()
            ->download($tmp, 'thong-bao-kiem-tra-ket-thuc-mon-'.$data['so_tb'].'-'.$safe.'.docx')
            ->deleteFileAfterSend(true);
    }

    /** Hạng ghi trong bảng thi lại: B1 / B11 → B, còn lại giữ nguyên (C1…). */
    private static function hangNgan(?string $hang): string
    {
        $hang = mb_strtoupper(trim((string) $hang));

        return in_array($hang, ['B1', 'B11'], true) ? 'B' : $hang;
    }

    private static function hoTenThuong(string $hoTen): string
    {
        return mb_convert_case(mb_strtolower($hoTen), MB_CASE_TITLE, 'UTF-8');
    }
}
