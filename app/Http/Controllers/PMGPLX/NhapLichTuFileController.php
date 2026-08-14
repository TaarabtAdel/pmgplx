<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\XeTap;
use App\Support\PMGPLX\LichCalendar;
use App\Support\PMGPLX\LichExcelBienSo;
use App\Support\PMGPLX\LichExcelDiaDiem;
use App\Support\PMGPLX\LichExcelTimeParser;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class NhapLichTuFileController extends Controller
{
    private const SESSION_EXCEL = 'pmgplx.lich.nhap_file.excel';

    private const SESSION_SAVE = 'pmgplx.lich.nhap_file.save';

    private const TEN_MON_HOC = 'Thực hành lái xe';

    public function create(): View
    {
        return view('PMGPLX.lich.nhap-tu-file');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:10240'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel (.xls, .xlsx) hoặc CSV.',
        ]);

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);

            $data = [];
            foreach ($rawRows as $row) {
                $normalized = [];
                foreach ($row as $colIndex => $value) {
                    $normalized[$colIndex] = $this->normalizeCell($value);
                }

                $isEmpty = collect($normalized)->every(fn ($v) => $v === null || $v === '');
                if ($isEmpty) {
                    continue;
                }

                $data[] = $normalized;
            }

            if (count($data) < 2) {
                return back()->with('error', 'File Excel không có dữ liệu lịch hợp lệ.');
            }

            $preview = $this->buildExcelPreview($data, $file->getClientOriginalName());
            if ($preview['teachers'] === []) {
                return back()->with('error', 'Không tìm thấy khối giáo viên (mỗi giáo viên cần 5 cột).');
            }

            $request->session()->forget(self::SESSION_SAVE);
            $request->session()->put(self::SESSION_EXCEL, $preview);

            return redirect()->route('pmgplx.lich.nhap-file.preview');
        } catch (Throwable $e) {
            return back()->with('error', 'Không đọc được file Excel: '.$e->getMessage());
        }
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_EXCEL);
        if (! is_array($preview) || empty($preview['teachers'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu xem trước. Vui lòng chọn file Excel.');
        }

        return view('PMGPLX.lich.xem-truoc-nhap-file', [
            'preview' => $preview,
        ]);
    }

    /** Excel preview → build GV/Xe rows → màn xem trước lịch GV */
    public function toGvPreview(Request $request): RedirectResponse
    {
        $excel = $request->session()->get(self::SESSION_EXCEL);
        if (! is_array($excel) || empty($excel['teachers'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu Excel. Vui lòng chọn file.');
        }

        $built = $this->buildSaveRows($excel);
        if ($built['gv_rows'] === []) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview')
                ->with('error', 'Không có buổi nào để lưu (toàn ngày nghỉ hoặc thiếu giờ/ngày).');
        }

        $request->session()->put(self::SESSION_SAVE, $built);

        return redirect()->route('pmgplx.lich.nhap-file.preview-gv');
    }

    public function previewGv(Request $request): View|RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['gv_rows'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview')
                ->with('error', 'Chưa có dữ liệu lịch giáo viên.');
        }

        $rows = $save['gv_rows'];
        $conflictCount = 0;
        foreach ($rows as &$row) {
            $conflict = $this->gvConflict($row['MaGV'], $row['NgayBD'], $row['NgayKT']);
            $row['conflict'] = $conflict;
            $row['ghi_chu'] = $conflict ? 'Đã thêm vào lịch' : '';
            if ($conflict) {
                $conflictCount++;
            }
        }
        unset($row);

        $save['gv_rows'] = $rows;
        $save['meta']['gv_conflict_count'] = $conflictCount;
        $save['meta']['gv_ok_count'] = count($rows) - $conflictCount;
        $request->session()->put(self::SESSION_SAVE, $save);

        $monHocs = DmMonHoc::active()
            ->orderBy('MaMH')
            ->get(['MaMH', 'TenMH']);

        $defaultMaMonHoc = '';
        $defaultTenMonHoc = self::TEN_MON_HOC;
        foreach ($monHocs as $mh) {
            $ten = mb_strtolower(trim((string) $mh->TenMH));
            $tenPlain = strtr($ten, [
                'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
                'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
                'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
                'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
                'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
                'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
                'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
                'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
                'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
                'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
                'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
                'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
                'đ' => 'd',
            ]);
            if (str_contains($tenPlain, 'thuc hanh lai xe') || str_contains($ten, 'thực hành lái xe')) {
                $defaultMaMonHoc = trim((string) $mh->MaMH);
                $defaultTenMonHoc = trim((string) $mh->TenMH);
                break;
            }
        }

        foreach ($rows as &$row) {
            $row['MaMonHoc'] = trim((string) ($row['MaMonHoc'] ?? ''));
            if ($row['MaMonHoc'] === '' && $defaultMaMonHoc !== '') {
                $row['MaMonHoc'] = $defaultMaMonHoc;
                $row['TenMonHoc'] = $defaultTenMonHoc;
            }
        }
        unset($row);

        $save['gv_rows'] = $rows;
        $request->session()->put(self::SESSION_SAVE, $save);

        return view('PMGPLX.lich.xem-truoc-nhap-file-gv', [
            'preview' => $save,
            'rows' => $rows,
            'monHocs' => $monHocs,
            'defaultMaMonHoc' => $defaultMaMonHoc,
        ]);
    }

    /** Lưu chỉnh sửa GV → màn xem trước lịch xe */
    public function toXePreview(Request $request): RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['gv_rows'])) {
            return redirect()->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Phiên xem trước đã hết hạn.');
        }

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.MaKH' => ['required', 'string', 'max:50'],
            'rows.*.MaGV' => ['required', 'string', 'max:20'],
            'rows.*.TenGV' => ['required', 'string', 'max:255'],
            'rows.*.MaMonHoc' => ['required', 'string', 'max:20'],
            'rows.*.NgayBD' => ['required', 'date'],
            'rows.*.NgayKT' => ['required', 'date'],
            'rows.*.source_key' => ['nullable', 'string'],
        ], [
            'rows.*.MaKH.required' => 'Mã khóa học không được để trống.',
            'rows.*.MaGV.required' => 'Mã giáo viên không được để trống.',
            'rows.*.TenGV.required' => 'Tên giáo viên không được để trống.',
            'rows.*.MaMonHoc.required' => 'Vui lòng chọn môn học.',
            'rows.*.NgayBD.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'rows.*.NgayKT.required' => 'Vui lòng chọn thời gian kết thúc.',
        ]);

        $monMap = DmMonHoc::query()
            ->whereIn('MaMH', collect($validated['rows'])->pluck('MaMonHoc')->unique()->filter()->all())
            ->pluck('TenMH', 'MaMH');

        $gvRows = [];
        foreach ($validated['rows'] as $i => $row) {
            $ngayBD = Carbon::parse($row['NgayBD']);
            $ngayKT = Carbon::parse($row['NgayKT']);
            if ($ngayKT->lte($ngayBD)) {
                $ngayKT = $ngayBD->copy()->addDay()->setTimeFromTimeString($ngayKT->format('H:i:s'));
            }

            $sourceKey = (string) ($row['source_key'] ?? '');
            $old = $save['gv_rows'][$i] ?? [];
            $maMonHoc = trim($row['MaMonHoc']);

            $gvRows[] = [
                'MaKH' => trim($row['MaKH']),
                'MaGV' => trim($row['MaGV']),
                'TenGV' => trim($row['TenGV']),
                'MaMonHoc' => $maMonHoc,
                'TenMonHoc' => (string) ($monMap[$maMonHoc] ?? ($old['TenMonHoc'] ?? '')),
                'DiaDiem' => '',
                'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                'noi_dung' => $old['noi_dung'] ?? '',
                'chi_tiet' => $old['chi_tiet'] ?? '',
                'bien_so_xe' => $old['bien_so_xe'] ?? '',
                'source_key' => $sourceKey !== '' ? $sourceKey : ($old['source_key'] ?? (string) $i),
                'conflict' => false,
                'ghi_chu' => '',
            ];
        }

        // Đồng bộ xe rows theo GV đã sửa (cùng source_key)
        $xeByKey = collect($save['xe_rows'] ?? [])->keyBy('source_key');
        $xeRows = [];
        foreach ($gvRows as $gv) {
            $key = $gv['source_key'];
            $oldXe = $xeByKey->get($key, []);
            $noiDung = (string) ($gv['noi_dung'] ?? '');
            $chiTiet = (string) ($gv['chi_tiet'] ?? '');
            $diaDiem = (string) ($oldXe['DiaDiem'] ?? LichExcelDiaDiem::resolve($noiDung, $chiTiet));
            $bienSo = LichExcelBienSo::normalize((string) ($oldXe['BienSoXe'] ?? $gv['bien_so_xe'] ?? ''));
            // Ưu tiên biển số từ nội dung TỰ ĐỘNG nếu có
            $bienSoTuDong = LichExcelBienSo::extractFromTuDong($noiDung, $chiTiet);
            if ($bienSoTuDong !== null) {
                $bienSo = $bienSoTuDong;
            }

            $xeRows[] = [
                'MaKH' => $gv['MaKH'],
                'MaGV' => $gv['MaGV'],
                'TenGV' => $gv['TenGV'],
                'BienSoXe' => $bienSo,
                'DiaDiem' => $diaDiem,
                'NgayBD' => $gv['NgayBD'],
                'NgayKT' => $gv['NgayKT'],
                'noi_dung' => $noiDung,
                'chi_tiet' => $chiTiet,
                'source_key' => $key,
                'conflict' => false,
                'ghi_chu' => '',
            ];
        }

        $save['gv_rows'] = $gvRows;
        $save['xe_rows'] = $xeRows;
        $request->session()->put(self::SESSION_SAVE, $save);

        return redirect()->route('pmgplx.lich.nhap-file.preview-xe');
    }

    public function previewXe(Request $request): View|RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['xe_rows'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview-gv')
                ->with('error', 'Chưa có dữ liệu lịch xe tập lái.');
        }

        $rows = $save['xe_rows'];
        $conflictCount = 0;
        foreach ($rows as &$row) {
            $conflict = $this->xeConflict($row['MaGV'], $row['BienSoXe'] ?? '', $row['NgayBD'], $row['NgayKT']);
            $row['conflict'] = $conflict;
            $row['ghi_chu'] = $conflict
                ? 'Đã thêm vào lịch'
                : (trim((string) ($row['BienSoXe'] ?? '')) === '' ? 'Chưa gắn xe' : '');
            if ($conflict) {
                $conflictCount++;
            }
        }
        unset($row);

        $save['xe_rows'] = $rows;
        $save['meta']['xe_conflict_count'] = $conflictCount;
        $save['meta']['xe_ok_count'] = count($rows) - $conflictCount;
        $request->session()->put(self::SESSION_SAVE, $save);

        $xeTaps = XeTap::query()->orderBy('BienSoXe')->pluck('BienSoXe');

        return view('PMGPLX.lich.xem-truoc-nhap-file-xe', [
            'preview' => $save,
            'rows' => $rows,
            'xeTaps' => $xeTaps,
            'diaDiems' => array_values(array_filter(
                array_unique(array_merge(KhoaHoc::$DIA_DIEM, LichExcelDiaDiem::options())),
                fn ($v) => $v !== ''
            )),
        ]);
    }

    /** Cập nhật chỉnh sửa xe → màn preview mảng sẽ lưu DB */
    public function toDbPreview(Request $request): RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['xe_rows'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu để xác nhận.');
        }

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.MaKH' => ['required', 'string', 'max:50'],
            'rows.*.MaGV' => ['required', 'string', 'max:20'],
            'rows.*.TenGV' => ['required', 'string', 'max:255'],
            'rows.*.BienSoXe' => ['required', 'string', 'max:50'],
            'rows.*.DiaDiem' => ['required', 'string', 'max:255'],
            'rows.*.NgayBD' => ['required', 'date'],
            'rows.*.NgayKT' => ['required', 'date'],
            'rows.*.source_key' => ['nullable', 'string'],
        ], [
            'rows.*.MaKH.required' => 'Mã khóa học không được để trống.',
            'rows.*.MaGV.required' => 'Mã giáo viên không được để trống.',
            'rows.*.TenGV.required' => 'Tên giáo viên không được để trống.',
            'rows.*.BienSoXe.required' => 'Vui lòng chọn xe tập.',
            'rows.*.DiaDiem.required' => 'Vui lòng chọn địa điểm.',
            'rows.*.NgayBD.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'rows.*.NgayKT.required' => 'Vui lòng chọn thời gian kết thúc.',
        ]);

        $xeRows = [];
        foreach ($validated['rows'] as $row) {
            $ngayBD = Carbon::parse($row['NgayBD']);
            $ngayKT = Carbon::parse($row['NgayKT']);
            if ($ngayKT->lte($ngayBD)) {
                $ngayKT = $ngayBD->copy()->addDay()->setTimeFromTimeString($ngayKT->format('H:i:s'));
            }

            $xeRows[] = [
                'MaKH' => trim($row['MaKH']),
                'MaGV' => trim($row['MaGV']),
                'TenGV' => trim($row['TenGV']),
                'BienSoXe' => LichExcelBienSo::normalize(trim((string) ($row['BienSoXe'] ?? ''))),
                'DiaDiem' => trim((string) ($row['DiaDiem'] ?? '')),
                'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                'source_key' => (string) ($row['source_key'] ?? ''),
            ];
        }

        $xeByKey = collect($xeRows)->keyBy('source_key');
        $gvRows = [];
        foreach ($save['gv_rows'] as $gv) {
            $key = (string) ($gv['source_key'] ?? '');
            $xe = $xeByKey->get($key);
            if ($xe) {
                $gv['MaKH'] = $xe['MaKH'];
                $gv['MaGV'] = $xe['MaGV'];
                $gv['TenGV'] = $xe['TenGV'];
                $gv['NgayBD'] = $xe['NgayBD'];
                $gv['NgayKT'] = $xe['NgayKT'];
                $gv['bien_so_xe'] = $xe['BienSoXe'];
            }
            $gvRows[] = $gv;
        }

        $save['gv_rows'] = $gvRows;
        $save['xe_rows'] = $xeRows;
        $save['db_payload'] = $this->buildDbPayload($gvRows, $xeRows);
        $request->session()->put(self::SESSION_SAVE, $save);

        return redirect()->route('pmgplx.lich.nhap-file.preview-db');
    }

    public function previewDb(Request $request): View|RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['db_payload'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview-xe')
                ->with('error', 'Chưa có dữ liệu preview lưu DB.');
        }

        return view('PMGPLX.lich.xem-truoc-nhap-file-db', [
            'preview' => $save,
            'payload' => $save['db_payload'],
        ]);
    }

    /** Xác nhận cuối (chưa lưu DB) */
    public function confirm(Request $request): RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['db_payload'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu để xác nhận.');
        }

        $gvCount = count($save['db_payload']['lich_giao_vien'] ?? []);
        $xeCount = count($save['db_payload']['lich_xe_tap'] ?? []);
        $gvSkip = count($save['db_payload']['lich_giao_vien_bo_qua'] ?? []);
        $xeSkip = count($save['db_payload']['lich_xe_tap_bo_qua'] ?? []);

        $request->session()->forget([self::SESSION_EXCEL, self::SESSION_SAVE]);

        return redirect()
            ->route('pmgplx.lich.nhap-file.create')
            ->with(
                'success',
                "Đã xác nhận preview (chưa lưu DB): GV lưu {$gvCount}/bỏ {$gvSkip}, xe lưu {$xeCount}/bỏ {$xeSkip}."
            );
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows
     * @param  list<array<string, mixed>>  $xeRows
     * @return array<string, mixed>
     */
    private function buildDbPayload(array $gvRows, array $xeRows): array
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $lichGv = [];
        $lichGvSkip = [];
        $lichXe = [];
        $lichXeSkip = [];

        foreach ($gvRows as $row) {
            $conflict = $this->gvConflict($row['MaGV'], $row['NgayBD'], $row['NgayKT']);
            $record = [
                'MaKH' => $row['MaKH'],
                'MaGV' => $row['MaGV'],
                'TenGV' => $row['TenGV'],
                'LoaiGV' => 'TH',
                'GhiChu' => '',
                'TrangThai' => 1,
                'NgayTao' => $now,
                'NgaySua' => $now,
                'NgayBD' => $row['NgayBD'],
                'NgayKT' => $row['NgayKT'],
                'IsKhoaHocGiaoVien' => 0,
                'MaMonHoc' => $row['MaMonHoc'] ?? '',
                'TenMonHoc' => $row['TenMonHoc'] ?? self::TEN_MON_HOC,
            ];

            if ($conflict) {
                $record['_skip_reason'] = 'Đã thêm vào lịch (trùng GV)';
                $lichGvSkip[] = $record;
            } else {
                $lichGv[] = $record;
            }
        }

        foreach ($xeRows as $row) {
            $bienSo = trim((string) ($row['BienSoXe'] ?? ''));
            $conflict = $this->xeConflict($row['MaGV'], $bienSo, $row['NgayBD'], $row['NgayKT']);
            $record = [
                'MaKH' => $row['MaKH'],
                'BienSoXe' => $bienSo,
                'MaGV' => $row['MaGV'],
                'TenGV' => $row['TenGV'],
                'DiaDiem' => $row['DiaDiem'] ?? '',
                'GhiChu' => '',
                'TrangThai' => 1,
                'NgayBD' => $row['NgayBD'],
                'NgayKT' => $row['NgayKT'],
                'NgayTao' => $now,
                'NgaySua' => $now,
                'IsKhoaHocXeTap' => 0,
            ];

            if ($conflict) {
                $record['_skip_reason'] = 'Đã thêm vào lịch (trùng GV/xe)';
                $lichXeSkip[] = $record;
            } elseif ($bienSo === '') {
                $record['_skip_reason'] = 'Chưa gắn xe';
                $lichXeSkip[] = $record;
            } else {
                $lichXe[] = $record;
            }
        }

        return [
            'lich_giao_vien' => $lichGv,
            'lich_giao_vien_bo_qua' => $lichGvSkip,
            'lich_xe_tap' => $lichXe,
            'lich_xe_tap_bo_qua' => $lichXeSkip,
            'meta' => [
                'gv_save' => count($lichGv),
                'gv_skip' => count($lichGvSkip),
                'xe_save' => count($lichXe),
                'xe_skip' => count($lichXeSkip),
            ],
        ];
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_EXCEL, self::SESSION_SAVE]);

        return redirect()->route('pmgplx.lich.nhap-file.create');
    }

    /**
     * @param  array<string, mixed>  $excel
     * @return array<string, mixed>
     */
    private function buildSaveRows(array $excel): array
    {
        $gvRows = [];
        $xeRows = [];
        $khoaHocCache = [];
        $seq = 0;

        $defaultMon = DmMonHoc::active()
            ->where(function ($q) {
                $q->where('TenMH', self::TEN_MON_HOC)
                    ->orWhere('TenMH', 'like', '%Thực hành lái xe%');
            })
            ->orderBy('MaMH')
            ->first(['MaMH', 'TenMH']);

        // Fallback: bỏ dấu so sánh trong PHP
        if (! $defaultMon) {
            $defaultMon = DmMonHoc::active()
                ->orderBy('MaMH')
                ->get(['MaMH', 'TenMH'])
                ->first(function ($mh) {
                    $plain = mb_strtolower((string) preg_replace('/\s+/', ' ', $mh->TenMH));
                    $plain = strtr($plain, [
                        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
                        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
                        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
                        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
                        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
                        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
                        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
                        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
                        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
                        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
                        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
                        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
                        'đ' => 'd',
                    ]);

                    return str_contains($plain, 'thuc hanh lai xe');
                });
        }

        $defaultMaMonHoc = (string) ($defaultMon->MaMH ?? '');
        $defaultTenMonHoc = (string) ($defaultMon->TenMH ?? self::TEN_MON_HOC);

        foreach ($excel['teachers'] as $gv) {
            $maKh = (string) ($gv['ma_kh'] ?? '');
            $maGv = (string) ($gv['ma_gv'] ?? '');
            $tenGv = (string) ($gv['ten_gv'] ?? '');
            $bienSoMacDinh = LichExcelBienSo::normalize((string) ($gv['bien_so_xe'] ?? ''));

            if ($maKh !== '' && ! isset($khoaHocCache[$maKh])) {
                $khoaHocCache[$maKh] = KhoaHoc::query()->where('MaKH', $maKh)->value('TenKH') ?? $maKh;
            }

            foreach ($gv['rows'] as $row) {
                if (! empty($row['is_off'])) {
                    continue;
                }

                $date = LichExcelTimeParser::parseDate((string) ($row['ngay'] ?? ''));
                if ($date === null) {
                    continue;
                }

                $slots = LichExcelTimeParser::expandSlots(
                    (string) ($row['bat_dau'] ?? ''),
                    (string) ($row['ket_thuc'] ?? '')
                );

                $noiDung = (string) ($row['noi_dung'] ?? '');
                $chiTiet = (string) ($row['chi_tiet'] ?? '');
                $diaDiem = LichExcelDiaDiem::resolve($noiDung, $chiTiet);
                $bienSoTuDong = LichExcelBienSo::extractFromTuDong($noiDung, $chiTiet);
                $bienSo = $bienSoTuDong ?? $bienSoMacDinh;

                foreach ($slots as $slot) {
                    $ngayBD = LichCalendar::combineDateAndTime($date, $slot['start']);
                    $ngayKT = LichCalendar::combineDateAndTime($date, $slot['end']);
                    if ($ngayKT->lte($ngayBD)) {
                        $ngayKT->addDay();
                    }

                    $key = 'r'.$seq++;
                    $gvRows[] = [
                        'MaKH' => $maKh,
                        'MaGV' => $maGv,
                        'TenGV' => $tenGv,
                        'TenMonHoc' => $defaultTenMonHoc,
                        'MaMonHoc' => $defaultMaMonHoc,
                        'DiaDiem' => '',
                        'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                        'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                        'noi_dung' => $noiDung,
                        'chi_tiet' => $chiTiet,
                        'bien_so_xe' => $bienSo,
                        'source_key' => $key,
                        'conflict' => false,
                        'ghi_chu' => '',
                    ];

                    $xeRows[] = [
                        'MaKH' => $maKh,
                        'MaGV' => $maGv,
                        'TenGV' => $tenGv,
                        'BienSoXe' => $bienSo,
                        'DiaDiem' => $diaDiem,
                        'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                        'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                        'noi_dung' => $noiDung,
                        'chi_tiet' => $chiTiet,
                        'source_key' => $key,
                        'conflict' => false,
                        'ghi_chu' => '',
                    ];
                }
            }
        }

        $tenKhList = collect($gvRows)->pluck('MaKH')->unique()->map(
            fn ($ma) => ($khoaHocCache[$ma] ?? $ma).' ('.$ma.')'
        )->values()->all();

        $ngayList = collect($gvRows)
            ->map(fn ($r) => Carbon::parse($r['NgayBD'])->format('d/m/Y'))
            ->unique()
            ->values()
            ->all();

        return [
            'file_name' => $excel['file_name'] ?? '',
            'gv_rows' => $gvRows,
            'xe_rows' => $xeRows,
            'meta' => [
                'ten_mon_hoc' => self::TEN_MON_HOC,
                'khoa_hoc_list' => $tenKhList,
                'ngay_list' => $ngayList,
                'gv_count' => count($gvRows),
                'xe_count' => count($xeRows),
            ],
        ];
    }

    /**
     * @param  list<list<mixed>>  $data
     * @return array{file_name: string, teachers: list<array<string, mixed>>, meta: array<string, int>}
     */
    private function buildExcelPreview(array $data, string $fileName): array
    {
        $header = $data[0];
        $body = array_slice($data, 1);

        $maxCols = 0;
        foreach ($data as $row) {
            $maxCols = max($maxCols, count($row));
        }

        $teacherCount = intdiv($maxCols, 5);
        $teachers = [];
        $okCount = 0;
        $offCount = 0;

        for ($t = 0; $t < $teacherCount; $t++) {
            $base = $t * 5;
            $rawTenXe = (string) ($header[$base + 1] ?? '');
            [$tenGv, $bienSoXe] = $this->parseTenVaBienSo($rawTenXe);
            $rawMa = (string) ($header[$base + 2] ?? '');
            [$maGv, $maKh] = $this->parseMaGvVaMaKh($rawMa);

            $rows = [];
            foreach ($body as $row) {
                $ngay = (string) ($row[$base] ?? '');
                $noiDung = (string) ($row[$base + 1] ?? '');
                $chiTiet = (string) ($row[$base + 2] ?? '');
                $batDau = (string) ($row[$base + 3] ?? '');
                $ketThuc = (string) ($row[$base + 4] ?? '');

                if ($ngay === '' && $noiDung === '' && $chiTiet === '' && $batDau === '' && $ketThuc === '') {
                    continue;
                }

                $isOff = $noiDung === '';
                if ($isOff) {
                    $offCount++;
                } else {
                    $okCount++;
                }

                $rows[] = [
                    'ngay' => $ngay,
                    'noi_dung' => $noiDung,
                    'chi_tiet' => $chiTiet,
                    'bat_dau' => $batDau,
                    'ket_thuc' => $ketThuc,
                    'is_off' => $isOff,
                ];
            }

            if ($rows === [] && $tenGv === '' && $maGv === '' && $maKh === '') {
                continue;
            }

            $teachers[] = [
                'index' => $t + 1,
                'ten_gv' => $tenGv,
                'bien_so_xe' => $bienSoXe,
                'ten_xe_raw' => $rawTenXe,
                'ma_gv' => $maGv,
                'ma_kh' => $maKh,
                'ma_raw' => $rawMa,
                'rows' => $rows,
                'ok_count' => collect($rows)->where('is_off', false)->count(),
                'off_count' => collect($rows)->where('is_off', true)->count(),
            ];
        }

        return [
            'file_name' => $fileName,
            'teachers' => $teachers,
            'meta' => [
                'teacher_count' => count($teachers),
                'ok_count' => $okCount,
                'off_count' => $offCount,
            ],
        ];
    }

    private function gvConflict(string $maGv, string $ngayBD, string $ngayKT): bool
    {
        $bd = Carbon::parse($ngayBD);
        $kt = Carbon::parse($ngayKT);

        return KhoaHocGiaoVien::query()
            ->where('MaGV', $maGv)
            ->where('IsKhoaHocGiaoVien', 0)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->exists();
    }

    private function xeConflict(string $maGv, string $bienSoXe, string $ngayBD, string $ngayKT): bool
    {
        $bd = Carbon::parse($ngayBD);
        $kt = Carbon::parse($ngayKT);
        $bienSoXe = trim($bienSoXe);

        if ($bienSoXe !== '') {
            return KhoaHocXeTap::query()
                ->where(function ($q) use ($maGv, $bienSoXe) {
                    $q->where('MaGV', $maGv)->orWhere('BienSoXe', $bienSoXe);
                })
                ->where('NgayBD', '<', $kt)
                ->where('NgayKT', '>', $bd)
                ->exists();
        }

        return KhoaHocXeTap::query()
            ->where('MaGV', $maGv)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->exists();
    }

    /** @return array{0: string, 1: string} */
    private function parseMaGvVaMaKh(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['', ''];
        }

        if (preg_match('/^([^-]+)-(.+)$/', $raw, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$raw, ''];
    }

    /** @return array{0: string, 1: string} */
    private function parseTenVaBienSo(string $raw): array
    {
        $raw = trim(preg_replace("/\r\n|\r|\n/", "\n", $raw) ?? $raw);
        if ($raw === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*?)\s*[-–]?\s*\n\s*([A-Z0-9\-]+(?:\.\d+)?)\s*$/iu', $raw, $m)) {
            return [trim($m[1], " \t-–"), trim($m[2])];
        }

        if (preg_match('/^(.*?)\s*[-–]\s*([A-Z0-9\-]+(?:\.\d+)?)\s*$/iu', $raw, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        $parts = preg_split("/\n+/", $raw) ?: [$raw];

        return [trim((string) ($parts[0] ?? '')), trim((string) ($parts[1] ?? ''))];
    }

    private function normalizeCell(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
