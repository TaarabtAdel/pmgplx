<?php

namespace App\Http\Controllers;

use App\Models\KhoaHoc;
use App\Models\KhoaHocGiaoVien;
use App\Models\KhoaHocXeTap;
use App\Support\LichCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaoLichTapHangLoatController extends Controller
{
    public function create(Request $request): View
    {
        $khoaHocs = KhoaHoc::active()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH', 'NgayKG', 'NgayBG']);

        $maKH = $request->query('ma_kh', $khoaHocs->first()->MaKH ?? null);

        $giaoViens = collect();
        $xeTaps = collect();
        $calendarHtml = '';
        $selectedKhoaHoc = null;

        if ($maKH) {
            $selectedKhoaHoc = $khoaHocs->firstWhere('MaKH', $maKH);

            // ASP.NET: DISTINCT from KhoaHoc_GiaoVien WHERE MaKH AND TrangThai=1
            $giaoViens = KhoaHocGiaoVien::query()
                ->select('MaGV', 'TenGV')
                ->where('MaKH', $maKH)
                ->where('TrangThai', 1)
                ->distinct()
                ->orderBy('TenGV')
                ->get();

            // ASP.NET: DISTINCT BienSoXe FROM KhoaHoc_XeTap WHERE MaKH=@MaKH
            $xeTaps = KhoaHocXeTap::query()
                ->select('BienSoXe')
                ->where('MaKH', $maKH)
                ->distinct()
                ->orderBy('BienSoXe')
                ->pluck('BienSoXe');

            $calendarHtml = LichCalendar::render(
                $selectedKhoaHoc?->NgayKG,
                $selectedKhoaHoc?->NgayBG
            );
        }

        return view('lich.tao-lich-tap-hang-loat', [
            'khoaHocs' => $khoaHocs,
            'maKH' => $maKH,
            'giaoViens' => $giaoViens,
            'xeTaps' => $xeTaps,
            'calendarHtml' => $calendarHtml,
            'diaDiem' => old('dia_diem', 'Sân tập lái'),
            'gioBD' => old('gio_bd', '07:00'),
            'gioKT' => old('gio_kt', '11:00'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:13'],
            'ma_gv' => ['required', 'string', 'max:8'],
            'bien_so_xe' => ['required', 'string', 'max:10'],
            'dia_diem' => ['required', 'string', 'max:255'],
            'gio_bd' => ['required', 'string'],
            'gio_kt' => ['required', 'string'],
            'ngay_chon' => ['required', 'string'],
        ], [
            'ma_kh.required' => 'Vui lòng chọn khóa học.',
            'ma_gv.required' => 'Vui lòng chọn giáo viên.',
            'bien_so_xe.required' => 'Vui lòng chọn xe tập.',
            'ngay_chon.required' => 'Vui lòng chọn ít nhất một ngày tập.',
        ]);

        $dates = LichCalendar::parseSelectedDates($validated['ngay_chon']);
        if ($dates === []) {
            return back()->withInput()->with('error', 'Vui lòng chọn ít nhất một ngày tập.');
        }

        $teacher = KhoaHocGiaoVien::query()
            ->where('MaKH', $validated['ma_kh'])
            ->where('MaGV', $validated['ma_gv'])
            ->where('TrangThai', 1)
            ->first();

        if (!$teacher) {
            return back()->withInput()->with('error', 'Giáo viên không thuộc khóa học đã chọn.');
        }

        $gioBD = substr($validated['gio_bd'], 0, 5);
        $gioKT = substr($validated['gio_kt'], 0, 5);

        try {
            DB::beginTransaction();

            $saved = 0;
            foreach ($dates as $date) {
                $ngayBD = LichCalendar::combineDateAndTime($date, $gioBD);
                $ngayKT = LichCalendar::combineDateAndTime($date, $gioKT);

                if ($ngayKT->lte($ngayBD)) {
                    throw new \RuntimeException("Giờ kết thúc phải sau giờ bắt đầu (ngày {$date}).");
                }

                // Conflict: same teacher OR same vehicle overlapping time
                $conflict = KhoaHocXeTap::query()
                    ->where(function ($q) use ($validated) {
                        $q->where('MaGV', $validated['ma_gv'])
                            ->orWhere('BienSoXe', $validated['bien_so_xe']);
                    })
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException(
                        "Giáo viên hoặc xe bị trùng lịch ngày {$date} ({$gioBD}–{$gioKT})."
                    );
                }

                KhoaHocXeTap::create([
                    'MaKH' => $validated['ma_kh'],
                    'BienSoXe' => $validated['bien_so_xe'],
                    'MaGV' => $validated['ma_gv'],
                    'DiaDiem' => $validated['dia_diem'],
                    'GhiChu' => '',
                    'TrangThai' => 1,
                    'NgayBD' => $ngayBD,
                    'NgayKT' => $ngayKT,
                    'NgayTao' => Carbon::now(),
                    'NgaySua' => Carbon::now(),
                    'IsKhoaHocXeTap' => 0,
                    'TenGV' => $teacher->TenGV,
                ]);

                $saved++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('lich.xe.index', ['ma_kh' => $validated['ma_kh']])
            ->with('success', "Đã tạo lịch thực hành thành công ({$saved} buổi).");
    }
}
