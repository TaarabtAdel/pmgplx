<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatLichThucHanhDuAn;
use App\Models\DaoTao\DatLichThucHanhPhienBan;
use App\Models\DaoTao\XeTapLai;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use App\Support\DaoTao\LichThucHanh\CauHinhDefaults;
use App\Support\DaoTao\LichThucHanh\LichExcelExporter;
use App\Support\DaoTao\LichThucHanh\LichGenerator;
use App\Support\DaoTao\LichThucHanh\LichValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatLichThucHanhController extends Controller
{
    public function index(): View
    {
        $duAn = DatLichThucHanhDuAn::query()
            ->orderByDesc('NgayCapNhat')
            ->limit(50)
            ->get();

        return view('DaoTao.dat.lich-thuc-hanh.index', [
            'duAnList' => $duAn,
        ]);
    }

    public function create(Request $request): View
    {
        $maKhoa = trim((string) $request->input('ma_khoa', ''));
        $hang = (string) $request->input('hang_dao_tao', $request->input('hang', 'B'));

        $cauHinh = CauHinhDefaults::khung($maKhoa, $hang);
        if ($hang === 'B.01' || $hang === 'B01') {
            $cauHinh['chuong_trinh'] = CauHinhDefaults::chuongTrinhHangB01();
        }
        foreach (['ngay_khai_giang', 'ngay_ket_thuc_du_kien'] as $field) {
            $v = trim((string) $request->input($field, ''));
            if ($v !== '') {
                $cauHinh[$field] = $v;
            }
        }
        if ($request->filled('he_so_quy_doi')) {
            $cauHinh['he_so_quy_doi'] = (float) $request->input('he_so_quy_doi');
        }
        if ($request->filled('gio_day_moi_ngay')) {
            $cauHinh['gio_day_moi_ngay'] = (int) $request->input('gio_day_moi_ngay');
        }
        if ($request->filled('so_hoc_vien_mac_dinh')) {
            $cauHinh['so_hoc_vien_mac_dinh'] = (int) $request->input('so_hoc_vien_mac_dinh');
        }

        return view('DaoTao.dat.lich-thuc-hanh.edit', [
            'duAn' => null,
            'cauHinh' => $cauHinh,
            'giaoViens' => PhanCongTuLichPmgplxQuery::filterGiaoVienOptions(),
            'xeTaps' => XeTapLai::query()->orderBy('BienSo')->get(),
            'dieuKienHang' => DatDieuKienDat::query()->orderBy('ThuTu')->get(),
            'phienBan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedMeta($request);
        $cauHinh = $this->cauHinhFromRequest($request, $validated, []);

        $duAn = DatLichThucHanhDuAn::query()->create([
            'MaKhoaHoc' => $validated['ma_khoa'],
            'HangDaoTao' => $validated['hang_dao_tao'],
            'NgayKhaiGiang' => $validated['ngay_khai_giang'],
            'NgayKetThucDuKien' => $validated['ngay_ket_thuc_du_kien'] ?: null,
            'CauHinhJson' => json_encode($cauHinh, JSON_UNESCAPED_UNICODE),
            'NgayTao' => now(),
            'NgayCapNhat' => now(),
        ]);

        return redirect()
            ->route('daotao.pdt.dat.lich-thuc-hanh.edit', $duAn->Id)
            ->with('success', 'Đã tạo dự án lịch.');
    }

    public function edit(int $id): View
    {
        $duAn = DatLichThucHanhDuAn::query()->findOrFail($id);
        $hangHienThi = (string) request()->input('hang_dao_tao', $duAn->HangDaoTao);
        $cauHinh = CauHinhDefaults::merge($duAn->cauHinh(), $duAn->MaKhoaHoc, $hangHienThi);
        return view('DaoTao.dat.lich-thuc-hanh.edit', [
            'duAn' => $duAn,
            'cauHinh' => $cauHinh,
            'giaoViens' => PhanCongTuLichPmgplxQuery::filterGiaoVienOptions(),
            'xeTaps' => XeTapLai::query()->orderBy('BienSo')->get(),
            'dieuKienHang' => DatDieuKienDat::query()->orderBy('ThuTu')->get(),
            'coLichDaSinh' => $duAn->phienBans()->exists(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $duAn = DatLichThucHanhDuAn::query()->findOrFail($id);
        $validated = $this->validatedMeta($request);
        $cauHinh = $this->cauHinhFromRequest($request, $validated, $duAn->cauHinh());

        $duAn->update([
            'MaKhoaHoc' => $validated['ma_khoa'],
            'HangDaoTao' => $validated['hang_dao_tao'],
            'NgayKhaiGiang' => $validated['ngay_khai_giang'],
            'NgayKetThucDuKien' => $validated['ngay_ket_thuc_du_kien'] ?: null,
            'CauHinhJson' => json_encode($cauHinh, JSON_UNESCAPED_UNICODE),
            'NgayCapNhat' => now(),
        ]);

        return back()->with('success', 'Đã lưu cấu hình.');
    }

    public function generate(Request $request, int $id): RedirectResponse
    {
        $duAn = DatLichThucHanhDuAn::query()->findOrFail($id);
        $cauHinh = CauHinhDefaults::merge($duAn->cauHinh(), $duAn->MaKhoaHoc, $duAn->HangDaoTao);

        try {
            $generator = new LichGenerator;
            $result = $generator->generate($cauHinh);
            if (($result['lich']['dates'] ?? []) === []) {
                return back()->with('error', 'Không sinh được ngày nào: kiểm tra ngày khai giảng / ngày kết thúc (kết thúc phải sau khai giảng).');
            }
            $kiemTra = LichValidator::validate($cauHinh, $result['lich'], $result['tom_tat']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Sinh lịch thất bại: '.$e->getMessage());
        }

        $payload = [
            'LichJson' => json_encode($result['lich'], JSON_UNESCAPED_UNICODE),
            'TomTatJson' => json_encode($result['tom_tat'], JSON_UNESCAPED_UNICODE),
            'KiemTraJson' => json_encode($kiemTra, JSON_UNESCAPED_UNICODE),
            'OChinhTayJson' => '[]',
            'LaNhap' => false,
            'Ten' => 'Lịch',
        ];

        $pb = DatLichThucHanhPhienBan::query()
            ->where('DuAnId', $duAn->Id)
            ->orderByDesc('Id')
            ->first();

        if ($pb) {
            $pb->update($payload);
        } else {
            $pb = DatLichThucHanhPhienBan::query()->create(array_merge($payload, [
                'DuAnId' => $duAn->Id,
                'NgayTao' => now(),
            ]));
        }

        DatLichThucHanhPhienBan::query()
            ->where('DuAnId', $duAn->Id)
            ->where('Id', '!=', $pb->Id)
            ->delete();

        if ($result['lich']['meta']['ngay_ket_thuc_tinh'] ?? null) {
            $duAn->update(['NgayKetThucDuKien' => $result['lich']['meta']['ngay_ket_thuc_tinh']]);
        }

        return redirect()
            ->route('daotao.pdt.dat.lich-thuc-hanh.preview', $duAn->Id)
            ->with('success', 'Đã sinh lịch.');
    }

    public function preview(int $id): View|RedirectResponse
    {
        $duAn = DatLichThucHanhDuAn::query()->findOrFail($id);
        $phienBan = $this->latestPhienBan($duAn);
        if ($phienBan === null) {
            return redirect()
                ->route('daotao.pdt.dat.lich-thuc-hanh.edit', $duAn->Id)
                ->with('error', 'Chưa có lịch — bấm Sinh lịch trên màn cấu hình.');
        }

        return view('DaoTao.dat.lich-thuc-hanh.preview', [
            'duAn' => $duAn,
            'lich' => $phienBan->lich(),
            'kiemTra' => $phienBan->kiemTra(),
        ]);
    }

    public function previewLegacy(int $duAnId, int $phienBanId): RedirectResponse
    {
        DatLichThucHanhDuAn::query()->findOrFail($duAnId);

        return redirect()->route('daotao.pdt.dat.lich-thuc-hanh.preview', $duAnId);
    }

    public function export(int $id): StreamedResponse|RedirectResponse
    {
        $duAn = DatLichThucHanhDuAn::query()->findOrFail($id);
        $phienBan = $this->latestPhienBan($duAn);
        if ($phienBan === null) {
            return redirect()
                ->route('daotao.pdt.dat.lich-thuc-hanh.edit', $duAn->Id)
                ->with('error', 'Chưa có lịch để xuất.');
        }

        return LichExcelExporter::download($phienBan->lich(), $duAn->MaKhoaHoc);
    }

    public function exportLegacy(int $duAnId, int $phienBanId): RedirectResponse
    {
        DatLichThucHanhDuAn::query()->findOrFail($duAnId);

        return redirect()->route('daotao.pdt.dat.lich-thuc-hanh.export', $duAnId);
    }

    private function latestPhienBan(DatLichThucHanhDuAn $duAn): ?DatLichThucHanhPhienBan
    {
        return $duAn->phienBans()->orderByDesc('Id')->first();
    }

    /** @return array<string, mixed> */
    private function validatedMeta(Request $request): array
    {
        return $request->validate([
            'ma_khoa' => ['required', 'string', 'max:50'],
            'hang_dao_tao' => ['required', 'string', 'max:20'],
            'ngay_khai_giang' => ['required', 'date'],
            'ngay_ket_thuc_du_kien' => ['nullable', 'date', 'after_or_equal:ngay_khai_giang'],
            'he_so_quy_doi' => ['nullable', 'numeric', 'min:0'],
            'gio_day_moi_ngay' => ['nullable', 'integer', 'in:8,9,10'],
            'gio_day_moi_ngay_toi_da' => ['nullable', 'integer', 'min:1', 'max:24'],
        ], [
            'ngay_ket_thuc_du_kien.after_or_equal' => 'Ngày kết thúc phải từ ngày khai giảng trở đi (hoặc để trống).',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function cauHinhFromRequest(Request $request, array $validated, array $existing = []): array
    {
        $base = CauHinhDefaults::merge($existing, $validated['ma_khoa'], $validated['hang_dao_tao']);
        $base['ma_khoa'] = $validated['ma_khoa'];
        $base['hang_dao_tao'] = $validated['hang_dao_tao'];
        $base['ngay_khai_giang'] = $validated['ngay_khai_giang'];
        $base['ngay_ket_thuc_du_kien'] = $validated['ngay_ket_thuc_du_kien'] ?? '';
        $base['lich_den_ngay'] = $validated['ngay_ket_thuc_du_kien'] ?? ($base['lich_den_ngay'] ?? '');
        $base['he_so_quy_doi'] = (float) ($request->input('he_so_quy_doi', 2));
        $base['gio_day_moi_ngay'] = (int) ($request->input('gio_day_moi_ngay', 8));
        $base['gio_day_moi_ngay_toi_da'] = (int) ($request->input('gio_day_moi_ngay_toi_da', 10));
        $base['so_hoc_vien_mac_dinh'] = (int) ($request->input('so_hoc_vien_mac_dinh', 5));

        $base['cap_xe'] = $this->parseCapXeFromRequest($request);

        $chuongTrinh = $this->parseChuongTrinhFromRequest($request);
        if ($chuongTrinh !== []) {
            $base['chuong_trinh'] = $chuongTrinh;
        }

        $base['nghi_dinh_ky'] = $this->emptyNghiDinhKy();
        $base['nghi_co_dinh'] = $this->parseDateList($request->input('nghi_co_dinh', []));
        $base['nghi_rieng'] = $this->parseNghiRiengFromRequest($request);

        $hang = $validated['hang_dao_tao'];
        $dk = DatDieuKienDat::forHang($hang);
        if ($dk) {
            $base['dieu_kien_dat_hang'] = [
                'hang' => $dk->Hang,
                'tap_lai_ban_dem_gio' => (float) $dk->TapLaiBanDemGio,
                'xe_so_tu_dong_gio' => (float) $dk->XeSoTuDongGio,
                'gio_cao_toc_gio' => (float) $dk->GioCaoTocGio,
                'so_gio_hoc' => (float) $dk->SoGioHoc,
                'tong_quang_duong_km' => (float) $dk->TongQuangDuongKm,
            ];
        }

        return $base;
    }

    /** @return list<array<string, mixed>> */
    private function parseCapXeFromRequest(Request $request): array
    {
        $rows = $request->input('cap_xe', []);
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        $stt = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $bien = trim((string) ($row['bien_so'] ?? ''));
            $gvSang = trim((string) ($row['gv_sang'] ?? ''));
            $gvChieu = trim((string) ($row['gv_chieu'] ?? ''));
            if ($bien === '' && $gvSang === '' && $gvChieu === '') {
                continue;
            }
            $stt++;
            $out[] = [
                'stt' => (int) ($row['stt'] ?? 0) ?: $stt,
                'bien_so' => $bien,
                'gv_sang' => $gvSang,
                'gv_chieu' => $gvChieu,
                'ten_gv_sang' => trim((string) ($row['ten_gv_sang'] ?? '')),
                'ten_gv_chieu' => trim((string) ($row['ten_gv_chieu'] ?? '')),
                'so_hoc_vien_gv_sang' => (int) ($row['so_hoc_vien_gv_sang'] ?? $request->input('so_hoc_vien_mac_dinh', 5)),
                'so_hoc_vien_gv_chieu' => (int) ($row['so_hoc_vien_gv_chieu'] ?? $request->input('so_hoc_vien_mac_dinh', 5)),
            ];
        }
        usort($out, fn ($a, $b) => ((int) ($a['stt'] ?? 0)) <=> ((int) ($b['stt'] ?? 0)));

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function parseChuongTrinhFromRequest(Request $request): array
    {
        $rows = $request->input('chuong_trinh', []);
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $ma = trim((string) ($row['ma'] ?? ''));
            if ($ma === '') {
                continue;
            }
            $out[] = [
                'ma' => $ma,
                'ten' => trim((string) ($row['ten'] ?? $ma)),
                'gio' => (float) ($row['gio'] ?? 0),
                'tinh_dat' => array_key_exists('tinh_dat', $row) && $row['tinh_dat'] !== '0' && $row['tinh_dat'] !== false,
                'thu_tu' => (int) ($row['thu_tu'] ?? 0),
                'mau' => trim((string) ($row['mau'] ?? $ma)),
            ];
        }
        usort($out, fn ($a, $b) => ((int) ($a['thu_tu'] ?? 0)) <=> ((int) ($b['thu_tu'] ?? 0)));

        return $out;
    }

    /** @return array<string, mixed> */
    private function emptyNghiDinhKy(): array
    {
        return [
            'thu_trong_tuan' => '',
            'tu_ngay' => '',
            'den_ngay' => '',
            'ngoai_le_van_day' => [],
            'nghi_bu' => [],
        ];
    }

    /** @return list<string> */
    private function parseDateList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $d) {
            $d = trim((string) $d);
            if ($d !== '') {
                $out[] = $d;
            }
        }

        return array_values(array_unique($out));
    }

    /** @return list<array<string, mixed>> */
    private function parseNghiRiengFromRequest(Request $request): array
    {
        $rows = $request->input('nghi_rieng', []);
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $ngay = $this->parseDateList($row['ngay'] ?? []);
            if ($ngay === []) {
                $ngayRaw = trim((string) ($row['ngay_text'] ?? ''));
                if ($ngayRaw === '') {
                    continue;
                }
                $ngay = preg_split('/[\s,;]+/', $ngayRaw) ?: [];
                $ngay = array_values(array_unique(array_filter(array_map('trim', $ngay))));
            }
            if ($ngay === []) {
                continue;
            }
            $capStt = (int) ($row['cap_stt'] ?? 0);
            $bienSo = trim((string) ($row['bien_so'] ?? ''));
            if ($capStt <= 0 && $bienSo === '') {
                continue;
            }
            $out[] = [
                'cap_stt' => $capStt,
                'bien_so' => $bienSo,
                'ngay' => $ngay,
            ];
        }

        return $out;
    }
}
