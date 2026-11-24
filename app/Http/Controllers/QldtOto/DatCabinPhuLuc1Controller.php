<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatDSPhienLocResolver;
use App\Support\DaoTao\DatKhoaHocSelectOptions;
use App\Support\DaoTao\PhuLuc1DatBaoCaoHoTro as HoTro;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhuLuc1DatCabinDocx;
use App\Support\QldtOto\PhuLuc1DatCabinZip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatCabinPhuLuc1Controller extends Controller
{
    public const SLUG = '4-dat-cabin-phu-luc-1';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $filters = HoTro::filtersTuRequest($request);
        $resolved = DatDSPhienLocResolver::resolve($filters);
        $canAnalyzeViolations = $resolved['canAnalyzeViolations'];

        $hocViens = ($filters['ma_khoa_hoc'] ?? '') !== ''
            ? HoTro::danhSachHocVienTomTat($filters)
            : [];

        $khoaHocOptions = DatKhoaHocSelectOptions::fromDatDSPhien();

        $giaoVienOptions = DatDSPhien::query()
            ->selectRaw('MaGiaoVien, MAX(HoTenGiaoVien) as HoTenGiaoVien')
            ->whereNotNull('MaGiaoVien')
            ->where('MaGiaoVien', '!=', '')
            ->groupBy('MaGiaoVien')
            ->orderBy('MaGiaoVien')
            ->get();

        $xeOptions = DatDSPhien::query()
            ->whereNotNull('BienSoXe')
            ->where('BienSoXe', '!=', '')
            ->distinct()
            ->orderBy('BienSoXe')
            ->pluck('BienSoXe');

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if (($filters['ma_hoc_vien'] ?? '') !== '') {
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
                $selectedHocVienOption = [
                    'id' => DatDSPhienBoLoc::composeMaHocVienValue(
                        (string) $selectedRow->MaHocVien,
                        (string) $selectedRow->MaKhoaHoc,
                    ),
                    'text' => trim((string) ($selectedRow->HoTenHocVien ?? '')) !== ''
                        ? trim((string) $selectedRow->HoTenHocVien).' ('.$selectedRow->MaHocVien.')'
                        : (string) $selectedRow->MaHocVien,
                ];
            }
        }

        return view('QldtOto.dat-cabin-phu-luc-1', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'filters' => $filters,
            'hocViens' => $hocViens,
            'tongPhienLoc' => $resolved['filteredCount'],
            'canAnalyzeViolations' => $canAnalyzeViolations,
            'khoaHocOptions' => $khoaHocOptions,
            'giaoVienOptions' => $giaoVienOptions,
            'xeOptions' => $xeOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
            'ngay_bao_cao' => old('ngay_bao_cao', $request->input('ngay_bao_cao', now()->format('Y-m-d'))),
        ]);
    }

    public function exportMot(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = array_merge($this->validateXuat($request), $request->validate([
            'ma_hoc_vien' => ['required', 'string', 'max:120'],
        ]));

        $filters = $this->filtersTuValidated($validated);
        $maHocViens = HoTro::danhSachMaHocVien($filters, $validated['ma_hoc_vien']);
        if ($maHocViens === []) {
            return back()->withInput()->with('error', 'Không có phiên DAT cho học viên / bộ lọc hiện tại.');
        }

        try {
            $out = sys_get_temp_dir().'/'.uniqid('pl1-', true).'.docx';
            $this->xuatMotHocVien($maHocViens[0], $filters, $validated['ngay_bao_cao'], $out);

            return response()->download($out, PhuLuc1DatCabinDocx::tenFile($maHocViens[0]))->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $this->validateXuat($request);
        $filters = $this->filtersTuValidated($validated);

        $chiChon = array_values(array_filter((array) $request->input('ma_hoc_vien_chon', [])));
        $maHocViens = HoTro::danhSachMaHocVien(
            $filters,
            trim((string) ($validated['ma_hoc_vien'] ?? '')) ?: null,
            $chiChon !== [] ? $chiChon : null,
        );

        if ($maHocViens === []) {
            return back()->withInput()->with('error', 'Không có học viên / phiên DAT phù hợp bộ lọc.');
        }

        try {
            if (count($maHocViens) === 1) {
                $out = sys_get_temp_dir().'/'.uniqid('pl1-', true).'.docx';
                $this->xuatMotHocVien($maHocViens[0], $filters, $validated['ngay_bao_cao'], $out);

                return response()->download($out, PhuLuc1DatCabinDocx::tenFile($maHocViens[0]))->deleteFileAfterSend(true);
            }

            $zip = sys_get_temp_dir().'/'.uniqid('pl1-zip-', true).'.zip';
            PhuLuc1DatCabinZip::taiZip($filters, $maHocViens, $validated['ngay_bao_cao'], $zip);

            return response()->download($zip, PhuLuc1DatCabinZip::tenFileZip($validated['ma_khoa_hoc']))->deleteFileAfterSend(true);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function validateXuat(Request $request): array
    {
        return $request->validate([
            'ma_khoa_hoc' => ['required', 'string', 'max:50'],
            'ma_hoc_vien' => ['nullable', 'string', 'max:120'],
            'ma_phien' => ['nullable', 'string', 'max:80'],
            'ma_giao_vien' => ['nullable', 'string', 'max:50'],
            'loai_khoa_hoc' => ['nullable', 'string', 'max:50'],
            'bien_so_xe' => ['nullable', 'string', 'max:30'],
            'tu_ngay' => ['nullable', 'date'],
            'den_ngay' => ['nullable', 'date'],
            'dat' => ['nullable', 'string', 'in:,dat,chua_dat'],
            'ngay_bao_cao' => ['required', 'date'],
        ]);
    }

    /**
     * @param  array<string, string>  $validated
     * @return array<string, mixed>
     */
    private function filtersTuValidated(array $validated): array
    {
        return HoTro::filtersTuRequest(new Request([
            'ma_khoa_hoc' => $validated['ma_khoa_hoc'],
            'ma_hoc_vien' => $validated['ma_hoc_vien'] ?? '',
            'ma_phien' => $validated['ma_phien'] ?? '',
            'ma_giao_vien' => $validated['ma_giao_vien'] ?? '',
            'loai_khoa_hoc' => $validated['loai_khoa_hoc'] ?? '',
            'bien_so_xe' => $validated['bien_so_xe'] ?? '',
            'tu_ngay' => $validated['tu_ngay'] ?? '',
            'den_ngay' => $validated['den_ngay'] ?? '',
            'dat' => $validated['dat'] ?? '',
        ]));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function xuatMotHocVien(string $maHv, array $filters, string $ngayBaoCao, string $out): void
    {
        $ctx = HoTro::taiPhienVaKiemTra($filters);
        $theoHv = HoTro::nhomTheoHocVien($ctx['sessions']);
        $phien = $theoHv[$maHv] ?? [];
        if ($phien === []) {
            throw new \InvalidArgumentException('Không có phiên DAT cho học viên này.');
        }

        $tomTat = HoTro::baoCaoMotHocVien($maHv, $phien, $ctx['violationsById']);
        $ngaySinh = HoTro::ngaySinhTheoMaHocVien([$maHv]);

        PhuLuc1DatCabinDocx::generate([
            'ngay_bao_cao' => $ngayBaoCao,
            'ho_ten' => $tomTat['ho_ten'],
            'ma_hoc_vien' => $maHv,
            'ngay_sinh' => HoTro::ngaySinhHienThi($ngaySinh[$maHv] ?? null),
            'ma_khoa_hoc' => $tomTat['ma_khoa_hoc'],
            'ten_khoa_hoc' => $tomTat['ten_khoa_hoc'],
            'hang_dao_tao' => $tomTat['hang_dao_tao'],
            'phien' => $tomTat['phien'],
            'tong_thoi_gian' => $tomTat['tong_thoi_gian'],
            'tong_quang_duong' => $tomTat['tong_quang_duong'],
            'ket_luan' => HoTro::ketLuanChuongTrinh($maHv, $tomTat['hang_dao_tao'], $phien, $ctx['violationsById']),
        ], $out);
    }
}