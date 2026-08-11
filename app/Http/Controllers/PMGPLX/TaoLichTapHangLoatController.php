<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\XeTap;
use App\Support\PMGPLX\LichCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaoLichTapHangLoatController extends Controller
{
    private const SESSION_KEY = 'pmgplx.preview.thuc_hanh';

    public function create(Request $request): View
    {
        $khoaHocs = KhoaHoc::active()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH', 'NgayKG', 'NgayBG']);

        $diaDiem = KhoaHoc::$DIA_DIEM;
        $thangThi = KhoaHoc::$THANG_THI;

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
                ->from('KhoaHoc_GiaoVien as kg')
                ->leftJoin('GiaoVien as g', 'g.MaGV', '=', 'kg.MaGV')
                ->select('kg.MaGV', 'kg.TenGV', 'g.GhiChu as BienSoXe')
                ->where('kg.MaKH', $maKH)
                ->where('kg.TrangThai', 1)
                ->distinct()
                ->orderBy('kg.TenGV')
                ->get()
                ->unique('MaGV')
                ->values();
        }

        $selectedGiaoViens = old('ma_gv', []);
        if (! is_array($selectedGiaoViens)) {
            $selectedGiaoViens = [];
        }

        return view('PMGPLX.lich.tao-lich-tap-hang-loat', [
            'khoaHocs' => $khoaHocs,
            'maKH' => $maKH,
            'giaoViens' => $giaoViens,
            'diaDiem' => $diaDiem,
            'thangThi' => $thangThi,
            'selectedThang' => $selectedThang,
            'selectedNam' => $selectedNam,
            'daysOfMonth' => $daysOfMonth,
            'gioBD' => old('gio_bd', '07:00'),
            'gioKT' => old('gio_kt', '11:00'),
            'selectedGiaoViens' => array_values(array_filter($selectedGiaoViens)),
            'selectedDiaDiem' => old('dia_diem', $diaDiem[0] ?? ''),
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
            'dia_diem' => ['required', 'string', 'max:255'],
            'gio_bd' => ['required', 'string'],
            'gio_kt' => ['required', 'string'],
            'ngay_chon' => ['required', 'string'],
        ], [
            'ma_kh.required' => 'Vui lòng chọn khóa học.',
            'thang.required' => 'Vui lòng chọn tháng.',
            'ma_gv.required' => 'Vui lòng chọn ít nhất một giáo viên.',
            'ma_gv.min' => 'Vui lòng chọn ít nhất một giáo viên.',
            'ngay_chon.required' => 'Vui lòng chọn ít nhất một ngày tập.',
        ]);

        $dates = LichCalendar::parseSelectedDates($validated['ngay_chon']);
        if ($dates === []) {
            return back()->withInput()->with('error', 'Vui lòng chọn ít nhất một ngày tập.');
        }

        $maGVs = array_values(array_unique($validated['ma_gv']));

        $teachers = KhoaHocGiaoVien::query()
            ->where('MaKH', $validated['ma_kh'])
            ->whereIn('MaGV', $maGVs)
            ->where('TrangThai', 1)
            ->get(['MaGV', 'TenGV'])
            ->unique('MaGV')
            ->keyBy('MaGV');

        if ($teachers->count() !== count($maGVs)) {
            return back()->withInput()->with('error', 'Có giáo viên không thuộc khóa học đã chọn.');
        }

        $xeByGv = GiaoVien::query()
            ->whereIn('MaGV', $maGVs)
            ->get(['MaGV', 'GhiChu'])
            ->mapWithKeys(fn ($gv) => [$gv->MaGV => trim((string) ($gv->GhiChu ?? ''))]);

        $gioBD = substr($validated['gio_bd'], 0, 5);
        $gioKT = substr($validated['gio_kt'], 0, 5);
        $khoaHoc = KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->first();

        $rows = [];
        $conflictCount = 0;
        $missingXe = 0;

        foreach ($maGVs as $maGV) {
            $teacher = $teachers->get($maGV);
            $bienSoXe = $xeByGv->get($maGV, '');

            if ($bienSoXe === '') {
                $missingXe++;
            }

            foreach ($dates as $date) {
                $ngayBD = LichCalendar::combineDateAndTime($date, $gioBD);
                $ngayKT = LichCalendar::combineDateAndTime($date, $gioKT);

                if ($ngayKT->lte($ngayBD)) {
                    return back()->withInput()->with('error', "Giờ kết thúc phải sau giờ bắt đầu (ngày {$date}).");
                }

                $conflict = false;
                if ($bienSoXe !== '') {
                    $conflict = KhoaHocXeTap::query()
                        ->where(function ($q) use ($maGV, $bienSoXe) {
                            $q->where('MaGV', $maGV)
                                ->orWhere('BienSoXe', $bienSoXe);
                        })
                        ->where('NgayBD', '<', $ngayKT)
                        ->where('NgayKT', '>', $ngayBD)
                        ->exists();
                } else {
                    $conflict = KhoaHocXeTap::query()
                        ->where('MaGV', $maGV)
                        ->where('NgayBD', '<', $ngayKT)
                        ->where('NgayKT', '>', $ngayBD)
                        ->exists();
                }

                if ($conflict) {
                    $conflictCount++;
                }

                $rows[] = [
                    'MaKH' => $validated['ma_kh'],
                    'BienSoXe' => $bienSoXe,
                    'MaGV' => $maGV,
                    'TenGV' => $teacher->TenGV,
                    'DiaDiem' => $validated['dia_diem'],
                    'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                    'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                    'conflict' => $conflict,
                    'ghi_chu' => $conflict
                        ? 'Đã thêm vào lịch'
                        : ($bienSoXe === '' ? 'Chưa gắn xe' : ''),
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
                    'ten_gv' => $teachers->pluck('TenGV')->implode(', '),
                    'conflict_count' => $conflictCount,
                    'ok_count' => count($rows) - $conflictCount,
                    'missing_xe' => $missingXe,
                ],
                'rows' => $rows,
            ],
        ]);

        return redirect()->route('pmgplx.lich.thuc-hanh.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('pmgplx.lich.thuc-hanh.create')
                ->with('error', 'Không có dữ liệu xem trước. Vui lòng tạo lịch lại.');
        }

        $conflictCount = 0;
        foreach ($preview['rows'] as &$row) {
            $ngayBD = Carbon::parse($row['NgayBD']);
            $ngayKT = Carbon::parse($row['NgayKT']);
            $bienSoXe = trim((string) ($row['BienSoXe'] ?? ''));

            if ($bienSoXe !== '') {
                $conflict = KhoaHocXeTap::query()
                    ->where(function ($q) use ($row, $bienSoXe) {
                        $q->where('MaGV', $row['MaGV'])
                            ->orWhere('BienSoXe', $bienSoXe);
                    })
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();
            } else {
                $conflict = KhoaHocXeTap::query()
                    ->where('MaGV', $row['MaGV'])
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();
            }

            $row['conflict'] = $conflict;
            $row['ghi_chu'] = $conflict
                ? 'Đã thêm vào lịch'
                : ($bienSoXe === '' ? 'Chưa gắn xe' : '');
            if ($conflict) {
                $conflictCount++;
            }
        }
        unset($row);

        $preview['meta']['conflict_count'] = $conflictCount;
        $preview['meta']['ok_count'] = count($preview['rows']) - $conflictCount;
        session([self::SESSION_KEY => $preview]);

        $xeTaps = XeTap::query()
            ->orderBy('BienSoXe')
            ->pluck('BienSoXe');

        return view('PMGPLX.lich.xem-truoc-thuc-hanh', [
            'preview' => $preview,
            'xeTaps' => $xeTaps,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['rows'])) {
            return redirect()
                ->route('pmgplx.lich.thuc-hanh.create')
                ->with('error', 'Phiên xem trước đã hết hạn. Vui lòng tạo lịch lại.');
        }

        $bienSos = $request->input('bien_so_xe', []);
        if (! is_array($bienSos)) {
            $bienSos = [];
        }

        foreach ($preview['rows'] as $i => &$row) {
            if (array_key_exists($i, $bienSos)) {
                $row['BienSoXe'] = trim((string) $bienSos[$i]);
            }
        }
        unset($row);

        session([self::SESSION_KEY => $preview]);

        $rows = $preview['rows'];
        $form = $preview['form'];
        $saved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (empty($row['conflict']) && trim((string) ($row['BienSoXe'] ?? '')) === '') {
                return redirect()
                    ->route('pmgplx.lich.thuc-hanh.preview')
                    ->with('error', 'Vui lòng chọn xe tập cho tất cả buổi chưa trùng lịch.');
            }
        }

        try {
            DB::beginTransaction();

            foreach ($rows as $row) {
                $ngayBD = Carbon::parse($row['NgayBD']);
                $ngayKT = Carbon::parse($row['NgayKT']);
                $bienSoXe = trim((string) ($row['BienSoXe'] ?? ''));

                $conflict = KhoaHocXeTap::query()
                    ->where(function ($q) use ($row, $bienSoXe) {
                        $q->where('MaGV', $row['MaGV']);
                        if ($bienSoXe !== '') {
                            $q->orWhere('BienSoXe', $bienSoXe);
                        }
                    })
                    ->where('NgayBD', '<', $ngayKT)
                    ->where('NgayKT', '>', $ngayBD)
                    ->exists();

                if ($conflict || $bienSoXe === '') {
                    $skipped++;
                    continue;
                }

                KhoaHocXeTap::create([
                    'MaKH' => $row['MaKH'],
                    'BienSoXe' => $bienSoXe,
                    'MaGV' => $row['MaGV'],
                    'DiaDiem' => $row['DiaDiem'],
                    'GhiChu' => '',
                    'TrangThai' => 1,
                    'NgayBD' => $ngayBD,
                    'NgayKT' => $ngayKT,
                    'NgayTao' => Carbon::now(),
                    'NgaySua' => Carbon::now(),
                    'IsKhoaHocXeTap' => 0,
                    'TenGV' => $row['TenGV'],
                ]);

                $saved++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('pmgplx.lich.thuc-hanh.preview')
                ->with('error', $e->getMessage());
        }

        session()->forget(self::SESSION_KEY);

        if ($saved === 0) {
            return redirect()
                ->route('pmgplx.lich.xe.index', ['ma_kh' => $form['ma_kh'] ?? null])
                ->with('error', "Không có buổi nào được lưu. Đã bỏ qua {$skipped} buổi trùng lịch/thiếu xe.");
        }

        $msg = "Đã tạo lịch thực hành thành công ({$saved} buổi).";
        if ($skipped > 0) {
            $msg .= " Đã bỏ qua {$skipped} buổi trùng lịch hoặc thiếu xe.";
        }

        return redirect()
            ->route('pmgplx.lich.xe.index', ['ma_kh' => $form['ma_kh'] ?? null])
            ->with('success', $msg);
    }

    public function cancel(): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        $form = is_array($preview['form'] ?? null) ? $preview['form'] : [];
        session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('pmgplx.lich.thuc-hanh.create', [
                'ma_kh' => $form['ma_kh'] ?? null,
                'thang' => $form['thang'] ?? null,
            ])
            ->withInput($form);
    }
}
