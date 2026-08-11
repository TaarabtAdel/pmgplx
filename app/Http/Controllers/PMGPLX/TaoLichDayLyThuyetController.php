<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Support\PMGPLX\LichCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaoLichDayLyThuyetController extends Controller
{
    private const SESSION_KEY = 'pmgplx.preview.ly_thuyet';

    public function create(Request $request): View
    {
        $khoaHocs = KhoaHoc::active()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH', 'NgayKG', 'NgayBG']);

        $diaDiem = KhoaHoc::$DIA_DIEM;
        $thangThi = KhoaHoc::$THANG_THI;

        // Chỉ lấy dữ liệu cũ khi quay lại từ validation/cancel (old input).
        // Không hydrate từ session preview khi vào form mới.
        $maKH = $request->query('ma_kh')
            ?? old('ma_kh')
            ?? ($khoaHocs->first()->MaKH ?? null);

        $selectedThang = $request->query('thang')
            ?? old('thang')
            ?? now()->format('m');
        $selectedThang = str_pad((string) $selectedThang, 2, '0', STR_PAD_LEFT);
        if (! array_key_exists($selectedThang, $thangThi)) {
            $selectedThang = now()->format('m');
        }

        $selectedNam = now()->year;
        $daysOfMonth = Carbon::createFromDate($selectedNam, (int) $selectedThang, 1)->daysInMonth;

        $giaoViens = collect();

        if ($maKH) {
            $giaoViens = KhoaHocGiaoVien::query()
                ->select('MaGV', 'TenGV')
                ->where('MaKH', $maKH)
                ->where('IsKhoaHocGiaoVien', 1)
                ->distinct()
                ->orderBy('TenGV')
                ->get()
                ->unique('MaGV')
                ->values();
        }

        $monHocs = DmMonHoc::active()
            ->orderBy('MaMH')
            ->get(['MaMH', 'TenMH']);

        $selectedGiaoViens = old('ma_gv', []);
        if (! is_array($selectedGiaoViens)) {
            $selectedGiaoViens = [];
        }

        return view('PMGPLX.lich.tao-lich-day-ly-thuyet', [
            'khoaHocs' => $khoaHocs,
            'maKH' => $maKH,
            'giaoViens' => $giaoViens,
            'monHocs' => $monHocs,
            'diaDiem' => $diaDiem,
            'thangThi' => $thangThi,
            'selectedThang' => $selectedThang,
            'selectedNam' => $selectedNam,
            'daysOfMonth' => $daysOfMonth,
            'gioBD' => old('gio_bd', '07:00'),
            'gioKT' => old('gio_kt', '11:00'),
            'selectedGiaoViens' => array_values(array_filter($selectedGiaoViens)),
            'selectedDiaDiem' => old('dia_diem', $diaDiem[0] ?? ''),
            'tenMonHoc' => old('ten_mon_hoc', ''),
            'ngayChon' => old('ngay_chon', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:13'],
            'thang' => ['required', 'string', 'size:2'],
            'ma_gv' => ['required', 'array', 'min:1'],
            'ma_gv.*' => ['required', 'string', 'max:8'],
            'ten_mon_hoc' => ['required', 'string', 'max:255'],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'gio_bd' => ['required', 'string'],
            'gio_kt' => ['required', 'string'],
            'ngay_chon' => ['required', 'string'],
        ], [
            'ma_kh.required' => 'Vui lòng chọn khóa học.',
            'thang.required' => 'Vui lòng chọn tháng.',
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
        $khoaHoc = KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->first();

        $rows = [];
        $conflictCount = 0;

        foreach ($maGVs as $maGV) {
            $teacher = $teachers->get($maGV);

            foreach ($dates as $date) {
                $ngayBD = LichCalendar::combineDateAndTime($date, $gioBD);
                $ngayKT = LichCalendar::combineDateAndTime($date, $gioKT);

                if ($ngayKT->lte($ngayBD)) {
                    return back()->withInput()->with('error', "Giờ kết thúc phải sau giờ bắt đầu (ngày {$date}).");
                }

                $conflict = KhoaHocGiaoVien::query()
                    ->where('MaGV', $maGV)
                    ->where('IsKhoaHocGiaoVien', 0)
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();

                if ($conflict) {
                    $conflictCount++;
                }

                $rows[] = [
                    'MaKH' => $validated['ma_kh'],
                    'MaGV' => $maGV,
                    'TenGV' => $teacher->TenGV,
                    'TenMonHoc' => $validated['ten_mon_hoc'],
                    'DiaDiem' => $validated['dia_diem'] ?? '',
                    'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                    'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                    'conflict' => $conflict,
                    'ghi_chu' => $conflict ? 'Đã thêm vào lịch' : '',
                ];
            }
        }

        $validated['gio_bd'] = $gioBD;
        $validated['gio_kt'] = $gioKT;
        $validated['ma_gv'] = $maGVs;

        session([
            self::SESSION_KEY => [
                'form' => $validated,
                'meta' => [
                    'ten_kh' => $khoaHoc?->TenKH ?? $validated['ma_kh'],
                    'conflict_count' => $conflictCount,
                    'ok_count' => count($rows) - $conflictCount,
                ],
                'rows' => $rows,
            ],
        ]);

        return redirect()->route('pmgplx.lich.ly-thuyet.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('pmgplx.lich.ly-thuyet.create')
                ->with('error', 'Không có dữ liệu xem trước. Vui lòng tạo lịch lại.');
        }

        // Refresh conflict flags against current DB
        $conflictCount = 0;
        foreach ($preview['rows'] as &$row) {
            $ngayBD = Carbon::parse($row['NgayBD']);
            $ngayKT = Carbon::parse($row['NgayKT']);
            $conflict = KhoaHocGiaoVien::query()
                ->where('MaGV', $row['MaGV'])
                ->where('IsKhoaHocGiaoVien', 0)
                ->where('NgayBD', '<', $ngayKT)
                ->where('NgayKT', '>', $ngayBD)
                ->exists();
            $row['conflict'] = $conflict;
            $row['ghi_chu'] = $conflict ? 'Đã thêm vào lịch' : '';
            if ($conflict) {
                $conflictCount++;
            }
        }
        unset($row);

        $preview['meta']['conflict_count'] = $conflictCount;
        $preview['meta']['ok_count'] = count($preview['rows']) - $conflictCount;
        session([self::SESSION_KEY => $preview]);

        return view('PMGPLX.lich.xem-truoc-ly-thuyet', [
            'preview' => $preview,
        ]);
    }

    public function confirm(): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('pmgplx.lich.ly-thuyet.create')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng tạo lịch lại.');
        }

        $rows = $preview['rows'];
        $form = $preview['form'];
        $saved = 0;
        $skipped = 0;

        try {
            DB::beginTransaction();

            foreach ($rows as $row) {
                $ngayBD = Carbon::parse($row['NgayBD']);
                $ngayKT = Carbon::parse($row['NgayKT']);

                $conflict = KhoaHocGiaoVien::query()
                    ->where('MaGV', $row['MaGV'])
                    ->where('IsKhoaHocGiaoVien', 0)
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();

                if ($conflict) {
                    $skipped++;
                    continue;
                }

                KhoaHocGiaoVien::create([
                    'MaKH' => $row['MaKH'],
                    'MaGV' => $row['MaGV'],
                    'TenGV' => $row['TenGV'],
                    'LoaiGV' => 'LT',
                    'GhiChu' => '',
                    'TrangThai' => 1,
                    'NgayTao' => Carbon::now(),
                    'NgaySua' => Carbon::now(),
                    'NgayBD' => $ngayBD,
                    'NgayKT' => $ngayKT,
                    'IsKhoaHocGiaoVien' => 0,
                    'MaMonHoc' => null,
                    'TenMonHoc' => $row['TenMonHoc'],
                ]);

                $saved++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('pmgplx.lich.ly-thuyet.preview')
                ->with('error', $e->getMessage());
        }

        session()->forget(self::SESSION_KEY);

        if ($saved === 0) {
            return redirect()
                ->route('pmgplx.lich.gv.index', ['ma_kh' => $form['ma_kh'] ?? null])
                ->with('error', "Không có buổi nào được lưu. Đã bỏ qua {$skipped} buổi trùng lịch.");
        }

        $msg = "Đã tạo lịch dạy thành công ({$saved} buổi).";
        if ($skipped > 0) {
            $msg .= " Đã bỏ qua {$skipped} buổi trùng lịch (đã thêm vào lịch).";
        }

        return redirect()
            ->route('pmgplx.lich.gv.index', ['ma_kh' => $form['ma_kh'] ?? null])
            ->with('success', $msg);
    }

    public function cancel(): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        $form = is_array($preview['form'] ?? null) ? $preview['form'] : [];
        session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('pmgplx.lich.ly-thuyet.create', [
                'ma_kh' => $form['ma_kh'] ?? null,
                'thang' => $form['thang'] ?? null,
            ])
            ->withInput($form);
    }
}