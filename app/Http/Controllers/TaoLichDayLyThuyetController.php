<?php

namespace App\Http\Controllers;

use App\Models\DmMonHoc;
use App\Models\KhoaHoc;
use App\Models\KhoaHocGiaoVien;
use App\Support\LichCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaoLichDayLyThuyetController extends Controller
{
    public function create(Request $request): View
    {
        $khoaHocs = KhoaHoc::active()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH', 'NgayKG', 'NgayBG']);

        $maKH = $request->query('ma_kh', $khoaHocs->first()->MaKH ?? null);

        $giaoViens = collect();
        $calendarHtml = '';
        $selectedKhoaHoc = null;

        if ($maKH) {
            $selectedKhoaHoc = $khoaHocs->firstWhere('MaKH', $maKH);

            $giaoViens = KhoaHocGiaoVien::query()
                ->select('MaGV', 'TenGV')
                ->where('MaKH', $maKH)
                ->where('IsKhoaHocGiaoVien', 1)
                ->distinct()
                ->orderBy('TenGV')
                ->get()
                ->unique('MaGV')
                ->values();

            $calendarHtml = LichCalendar::render(
                $selectedKhoaHoc?->NgayKG,
                $selectedKhoaHoc?->NgayBG
            );
        }

        $monHocs = DmMonHoc::active()
            ->orderBy('MaMH')
            ->get(['MaMH', 'TenMH']);

        return view('lich.tao-lich-day-ly-thuyet', [
            'khoaHocs' => $khoaHocs,
            'maKH' => $maKH,
            'giaoViens' => $giaoViens,
            'monHocs' => $monHocs,
            'calendarHtml' => $calendarHtml,
            'diaDiem' => old('dia_diem', 'Phòng học lý thuyết'),
            'gioBD' => old('gio_bd', '07:00'),
            'gioKT' => old('gio_kt', '11:00'),
            'selectedGiaoViens' => array_values(array_filter((array) old('ma_gv', []))),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:13'],
            'ma_gv' => ['required', 'array', 'min:1'],
            'ma_gv.*' => ['required', 'string', 'max:8'],
            'ten_mon_hoc' => ['required', 'string', 'max:255'],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'gio_bd' => ['required', 'string'],
            'gio_kt' => ['required', 'string'],
            'ngay_chon' => ['required', 'string'],
        ], [
            'ma_kh.required' => 'Vui lòng chọn khóa học.',
            'ma_gv.required' => 'Vui lòng chọn ít nhất một giáo viên.',
            'ma_gv.min' => 'Vui lòng chọn ít nhất một giáo viên.',
            'ten_mon_hoc.required' => 'Vui lòng chọn môn học.',
            'ngay_chon.required' => 'Vui lòng chọn ít nhất một ngày dạy.',
        ]);

        $dates = LichCalendar::parseSelectedDates($validated['ngay_chon']);
        if ($dates === []) {
            return back()->withInput()->with('error', 'Vui lòng chọn ít nhất một ngày dạy.');
        }

        $maGVs = array_values(array_unique($validated['ma_gv']));

        $teachers = KhoaHocGiaoVien::query()
            ->where('MaKH', $validated['ma_kh'])
            ->whereIn('MaGV', $maGVs)
            ->where('IsKhoaHocGiaoVien', 1)
            ->get(['MaGV', 'TenGV'])
            ->unique('MaGV')
            ->keyBy('MaGV');

        if ($teachers->count() !== count($maGVs)) {
            return back()->withInput()->with('error', 'Có giáo viên không thuộc khóa học đã chọn.');
        }

        $gioBD = substr($validated['gio_bd'], 0, 5);
        $gioKT = substr($validated['gio_kt'], 0, 5);

        try {
            DB::beginTransaction();

            $saved = 0;
            foreach ($maGVs as $maGV) {
                $teacher = $teachers->get($maGV);

                foreach ($dates as $date) {
                    $ngayBD = LichCalendar::combineDateAndTime($date, $gioBD);
                    $ngayKT = LichCalendar::combineDateAndTime($date, $gioKT);

                    if ($ngayKT->lte($ngayBD)) {
                        throw new \RuntimeException("Giờ kết thúc phải sau giờ bắt đầu (ngày {$date}).");
                    }

                    $conflict = KhoaHocGiaoVien::query()
                        ->where('MaGV', $maGV)
                        ->where('IsKhoaHocGiaoVien', 0)
                        ->where('NgayBD', '<', $ngayKT)
                        ->where('NgayKT', '>', $ngayBD)
                        ->exists();

                    if ($conflict) {
                        throw new \RuntimeException(
                            "Giáo viên {$teacher->TenGV} bị trùng lịch ngày {$date} ({$gioBD}–{$gioKT})."
                        );
                    }

                    KhoaHocGiaoVien::create([
                        'MaKH' => $validated['ma_kh'],
                        'MaGV' => $maGV,
                        'TenGV' => $teacher->TenGV,
                        'LoaiGV' => 'LT',
                        'GhiChu' => '',
                        'TrangThai' => 1,
                        'NgayTao' => Carbon::now(),
                        'NgaySua' => Carbon::now(),
                        'NgayBD' => $ngayBD,
                        'NgayKT' => $ngayKT,
                        'IsKhoaHocGiaoVien' => 0,
                        'MaMonHoc' => null,
                        'TenMonHoc' => $validated['ten_mon_hoc'],
                    ]);

                    $saved++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        }

        $soGV = count($maGVs);

        return redirect()
            ->route('lich.gv.index', ['ma_kh' => $validated['ma_kh']])
            ->with('success', "Đã tạo lịch dạy thành công ({$saved} buổi cho {$soGV} giáo viên).");
    }
}
