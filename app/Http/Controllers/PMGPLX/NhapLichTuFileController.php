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
use App\Support\PMGPLX\LichExcelNoiDungSkip;
use App\Support\PMGPLX\LichExcelTimeParser;
use App\Support\PMGPLX\LichGvMonHoc;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class NhapLichTuFileController extends Controller
{
    private const SESSION_EXCEL = 'pmgplx.lich.nhap_file.excel';

    private const SESSION_SAVE = 'pmgplx.lich.nhap_file.save';

    private const TEN_MON_HOC = 'Thực hành lái xe';

    public const SUBMIT_CHUNK_SIZE = 500;

    /** Trên ngưỡng này gửi qua rows_json (tránh max_input_vars ~1000). */
    public const SUBMIT_CHUNK_MIN_ROWS = 120;

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
        $updateMode = ! empty($save['meta']['update_mode']);
        $conflictCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        foreach ($rows as &$row) {
            $noiDung = (string) ($row['noi_dung'] ?? '');
            $chiTiet = (string) ($row['chi_tiet'] ?? '');
            $skip = LichExcelNoiDungSkip::isGvSkip($noiDung, $chiTiet);
            $row['skip_save'] = $skip;
            $row['will_update'] = false;

            if ($skip) {
                $row['conflict'] = false;
                $row['ghi_chu'] = LichExcelNoiDungSkip::gvSkipLabel();
                $skipCount++;

                continue;
            }

            $hasConflict = $this->gvConflict($row['MaGV'], $row['NgayBD'], $row['NgayKT']);
            if ($hasConflict && $updateMode) {
                $row['conflict'] = false;
                $row['will_update'] = true;
                $row['ghi_chu'] = 'Sẽ cập nhật';
                $updateCount++;
            } elseif ($hasConflict) {
                $row['conflict'] = true;
                $row['ghi_chu'] = 'Đã thêm vào lịch';
                $conflictCount++;
            } else {
                $row['conflict'] = false;
                $row['ghi_chu'] = '';
            }
        }
        unset($row);

        $save['gv_rows'] = $rows;
        $save['meta']['gv_conflict_count'] = $conflictCount;
        $save['meta']['gv_update_count'] = $updateCount;
        $save['meta']['gv_skip_count'] = $skipCount;
        $save['meta']['gv_ok_count'] = count($rows) - $conflictCount - $skipCount;
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
            $selected = LichGvMonHoc::resolveSelected(
                $row['MaMonHoc'] ?? null,
                $row['TenMonHoc'] ?? null,
                LichGvMonHoc::normalizeMa($defaultMaMonHoc)
            );
            $row['MaMonHoc'] = $selected;
            $row['TenMonHoc'] = $selected !== null ? (string) $selected : '';
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
    public function toXePreview(Request $request): RedirectResponse|JsonResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['gv_rows'])) {
            return redirect()->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Phiên xem trước đã hết hạn.');
        }

        if ($request->filled('rows_json')) {
            return $this->submitGvChunked($request, $save);
        }

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.MaKH' => ['required', 'string', 'max:50'],
            'rows.*.MaGV' => ['required', 'string', 'max:20'],
            'rows.*.TenGV' => ['required', 'string', 'max:255'],
            'rows.*.MaMonHoc' => ['required', 'integer'],
            'rows.*.NgayBD' => ['required', 'date'],
            'rows.*.NgayKT' => ['required', 'date'],
            'rows.*.source_key' => ['nullable', 'string'],
        ], $this->gvRowValidationMessages());

        $updateMode = $request->boolean('che_do_cap_nhat');
        $gvRows = $this->buildGvRowsFromSubmission($save, $validated['rows']);
        $xeRows = $this->buildXeRowsFromGvRows($save, $gvRows);

        $save['gv_rows'] = $gvRows;
        $save['xe_rows'] = $xeRows;
        $save['meta']['update_mode'] = $updateMode;
        unset($save['gv_chunk_merge']);
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
        $updateModeXe = ! empty($save['meta']['update_mode_xe']);
        $conflictCount = 0;
        $updateCount = 0;
        $skipCount = 0;
        foreach ($rows as &$row) {
            $noiDung = (string) ($row['noi_dung'] ?? '');
            $chiTiet = (string) ($row['chi_tiet'] ?? '');
            $skip = LichExcelNoiDungSkip::isXeSkip($noiDung, $chiTiet);
            $row['skip_save'] = $skip;
            $row['will_update'] = false;

            if ($skip) {
                $row['conflict'] = false;
                $row['ghi_chu'] = LichExcelNoiDungSkip::xeSkipLabel($noiDung, $chiTiet);
                $skipCount++;

                continue;
            }

            $bienSo = trim((string) ($row['BienSoXe'] ?? ''));
            $hasConflict = $this->xeConflict($row['MaGV'], $bienSo, $row['NgayBD'], $row['NgayKT']);
            if ($hasConflict && $updateModeXe) {
                $row['conflict'] = false;
                $row['will_update'] = true;
                $row['ghi_chu'] = 'Sẽ cập nhật';
                $updateCount++;
            } elseif ($hasConflict) {
                $row['conflict'] = true;
                $row['ghi_chu'] = 'Đã thêm vào lịch';
                $conflictCount++;
            } else {
                $row['conflict'] = false;
                $row['ghi_chu'] = $bienSo === '' ? 'Chưa gắn xe' : '';
            }
        }
        unset($row);

        $save['xe_rows'] = $rows;
        $save['meta']['xe_conflict_count'] = $conflictCount;
        $save['meta']['xe_update_count'] = $updateCount;
        $save['meta']['xe_skip_count'] = $skipCount;
        $save['meta']['xe_ok_count'] = count($rows) - $conflictCount - $skipCount;
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
    public function toDbPreview(Request $request): RedirectResponse|JsonResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['xe_rows'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu để xác nhận.');
        }

        if ($request->filled('rows_json')) {
            return $this->submitXeChunked($request, $save);
        }

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.MaKH' => ['required', 'string', 'max:50'],
            'rows.*.MaGV' => ['required', 'string', 'max:20'],
            'rows.*.TenGV' => ['required', 'string', 'max:255'],
            'rows.*.BienSoXe' => ['nullable', 'string', 'max:50'],
            'rows.*.DiaDiem' => ['nullable', 'string', 'max:255'],
            'rows.*.NgayBD' => ['required', 'date'],
            'rows.*.NgayKT' => ['required', 'date'],
            'rows.*.source_key' => ['nullable', 'string'],
        ], $this->xeRowValidationMessages());

        $built = $this->buildXeRowsFromDbSubmission($save, $validated['rows']);
        if (isset($built['error'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview-xe')
                ->withErrors(['rows' => $built['error']])
                ->withInput();
        }

        $save['gv_rows'] = $built['gv_rows'];
        $save['xe_rows'] = $built['xe_rows'];
        $save['meta']['update_mode_xe'] = $request->boolean('che_do_cap_nhat');
        $save['db_payload'] = $this->buildDbPayload(
            $built['gv_rows'],
            $built['xe_rows'],
            ! empty($save['meta']['update_mode']),
            ! empty($save['meta']['update_mode_xe'])
        );
        unset($save['xe_chunk_merge']);
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

    /** Xác nhận cuối — ghi lịch GV và xe vào DB */
    public function confirm(Request $request): RedirectResponse
    {
        $save = $request->session()->get(self::SESSION_SAVE);
        if (! is_array($save) || empty($save['db_payload'])) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with('error', 'Chưa có dữ liệu để xác nhận.');
        }

        $payload = $save['db_payload'];
        $gvRows = $payload['lich_giao_vien'] ?? [];
        $xeRows = $payload['lich_xe_tap'] ?? [];
        $gvSkipPreview = count($payload['lich_giao_vien_bo_qua'] ?? []);
        $xeSkipPreview = count($payload['lich_xe_tap_bo_qua'] ?? []);

        if ($gvRows === [] && $xeRows === []) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.preview-db')
                ->with('error', 'Không có buổi nào đủ điều kiện lưu.');
        }

        $savedGv = 0;
        $updatedGv = 0;
        $savedXe = 0;
        $updatedXe = 0;
        $skippedGv = 0;
        $skippedXe = 0;
        $now = Carbon::now();

        try {
            DB::beginTransaction();

            foreach ($gvRows as $row) {
                $ngayBD = Carbon::parse($row['NgayBD']);
                $ngayKT = Carbon::parse($row['NgayKT']);
                $dbMon = LichGvMonHoc::dbFields($row['MaMonHoc'] ?? $row['TenMonHoc'] ?? null);

                if (($row['_action'] ?? '') === 'update') {
                    $existing = ! empty($row['MaLichLV'])
                        ? KhoaHocGiaoVien::query()->find($row['MaLichLV'])
                        : $this->findGvLichForUpdate($row['MaGV'], $row['NgayBD'], $row['NgayKT']);

                    if ($existing) {
                        $existing->update([
                            'MaKH' => $row['MaKH'],
                            'MaGV' => $row['MaGV'],
                            'TenGV' => $row['TenGV'],
                            'LoaiGV' => 'TH',
                            'GhiChu' => (string) ($row['GhiChu'] ?? ''),
                            'TrangThai' => 1,
                            'NgaySua' => $now,
                            'NgayBD' => $ngayBD,
                            'NgayKT' => $ngayKT,
                            'MaMonHoc' => $dbMon['MaMonHoc'],
                            'TenMonHoc' => $dbMon['TenMonHoc'],
                        ]);
                        $updatedGv++;

                        continue;
                    }

                    $skippedGv++;

                    continue;
                }

                if ($this->gvConflict($row['MaGV'], $row['NgayBD'], $row['NgayKT'])) {
                    $skippedGv++;

                    continue;
                }

                KhoaHocGiaoVien::create([
                    'MaKH' => $row['MaKH'],
                    'MaGV' => $row['MaGV'],
                    'TenGV' => $row['TenGV'],
                    'LoaiGV' => 'TH',
                    'GhiChu' => (string) ($row['GhiChu'] ?? ''),
                    'TrangThai' => 1,
                    'NgayTao' => $now,
                    'NgaySua' => $now,
                    'NgayBD' => $ngayBD,
                    'NgayKT' => $ngayKT,
                    'IsKhoaHocGiaoVien' => 0,
                    'MaMonHoc' => $dbMon['MaMonHoc'],
                    'TenMonHoc' => $dbMon['TenMonHoc'],
                ]);

                $savedGv++;
            }

            foreach ($xeRows as $row) {
                $ngayBD = Carbon::parse($row['NgayBD']);
                $ngayKT = Carbon::parse($row['NgayKT']);
                $bienSoXe = trim((string) ($row['BienSoXe'] ?? ''));

                if ($bienSoXe === '') {
                    $skippedXe++;

                    continue;
                }

                if (($row['_action'] ?? '') === 'update') {
                    $existing = ! empty($row['MaLichSD'])
                        ? KhoaHocXeTap::query()->find($row['MaLichSD'])
                        : $this->findXeLichForUpdate($row['MaGV'], $bienSoXe, $row['NgayBD'], $row['NgayKT']);

                    if ($existing) {
                        $existing->update([
                            'MaKH' => $row['MaKH'],
                            'BienSoXe' => $bienSoXe,
                            'MaGV' => $row['MaGV'],
                            'DiaDiem' => (string) ($row['DiaDiem'] ?? ''),
                            'GhiChu' => (string) ($row['GhiChu'] ?? ''),
                            'TrangThai' => 1,
                            'NgayBD' => $ngayBD,
                            'NgayKT' => $ngayKT,
                            'NgaySua' => $now,
                            'TenGV' => $row['TenGV'],
                        ]);
                        $updatedXe++;

                        continue;
                    }

                    $skippedXe++;

                    continue;
                }

                if ($this->xeConflict($row['MaGV'], $bienSoXe, $row['NgayBD'], $row['NgayKT'])) {
                    $skippedXe++;

                    continue;
                }

                KhoaHocXeTap::create([
                    'MaKH' => $row['MaKH'],
                    'BienSoXe' => $bienSoXe,
                    'MaGV' => $row['MaGV'],
                    'DiaDiem' => (string) ($row['DiaDiem'] ?? ''),
                    'GhiChu' => (string) ($row['GhiChu'] ?? ''),
                    'TrangThai' => 1,
                    'NgayBD' => $ngayBD,
                    'NgayKT' => $ngayKT,
                    'NgayTao' => $now,
                    'NgaySua' => $now,
                    'IsKhoaHocXeTap' => 0,
                    'TenGV' => $row['TenGV'],
                ]);

                $savedXe++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('pmgplx.lich.nhap-file.preview-db')
                ->with('error', 'Lưu DB thất bại: '.$e->getMessage());
        }

        $request->session()->forget([self::SESSION_EXCEL, self::SESSION_SAVE]);

        if ($savedGv === 0 && $savedXe === 0 && $updatedGv === 0 && $updatedXe === 0) {
            return redirect()
                ->route('pmgplx.lich.nhap-file.create')
                ->with(
                    'error',
                    "Không có buổi nào được lưu. Bỏ qua GV {$gvSkipPreview}, xe {$xeSkipPreview} (preview) + trùng lịch lúc lưu."
                );
        }

        $gvParts = [];
        if ($savedGv > 0) {
            $gvParts[] = "{$savedGv} mới";
        }
        if ($updatedGv > 0) {
            $gvParts[] = "{$updatedGv} cập nhật";
        }
        $msg = 'Đã lưu DB: GV '.($gvParts !== [] ? implode(', ', $gvParts) : '0').' buổi';
        $xeParts = [];
        if ($savedXe > 0) {
            $xeParts[] = "{$savedXe} mới";
        }
        if ($updatedXe > 0) {
            $xeParts[] = "{$updatedXe} cập nhật";
        }
        if ($xeParts !== []) {
            $msg .= ', xe '.implode(', ', $xeParts).' buổi';
        }
        $msg .= '.';
        $skipParts = [];
        if ($gvSkipPreview + $skippedGv > 0) {
            $skipParts[] = 'GV '.($gvSkipPreview + $skippedGv);
        }
        if ($xeSkipPreview + $skippedXe > 0) {
            $skipParts[] = 'xe '.($xeSkipPreview + $skippedXe);
        }
        if ($skipParts !== []) {
            $msg .= ' Bỏ qua '.implode(', ', $skipParts).'.';
        }

        return redirect()
            ->route('pmgplx.lich.nhap-file.create')
            ->with('success', $msg);
    }

    /**
     * @param  list<array<string, mixed>>  $gvRows
     * @param  list<array<string, mixed>>  $xeRows
     * @return array<string, mixed>
     */
    private function buildDbPayload(array $gvRows, array $xeRows, bool $updateModeGv = false, bool $updateModeXe = false): array
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $lichGv = [];
        $lichGvSkip = [];
        $lichGvUpdate = [];
        $lichXe = [];
        $lichXeSkip = [];
        $lichXeUpdate = [];

        foreach ($gvRows as $row) {
            $conflict = $this->gvConflict($row['MaGV'], $row['NgayBD'], $row['NgayKT']);
            $dbMon = LichGvMonHoc::dbFields($row['MaMonHoc'] ?? $row['TenMonHoc'] ?? null);
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
                'MaMonHoc' => $dbMon['MaMonHoc'],
                'TenMonHoc' => $dbMon['TenMonHoc'],
            ];

            if ($conflict && $updateModeGv) {
                $existing = $this->findGvLichForUpdate($row['MaGV'], $row['NgayBD'], $row['NgayKT']);
                if ($existing) {
                    $record['MaLichLV'] = $existing->MaLichLV;
                    $record['_action'] = 'update';
                    $lichGv[] = $record;
                    $lichGvUpdate[] = $record;

                    continue;
                }

                $record['_skip_reason'] = 'Trùng lịch nhưng không tìm thấy bản ghi để cập nhật';
                $lichGvSkip[] = $record;
            } elseif ($conflict) {
                $record['_skip_reason'] = 'Đã thêm vào lịch (trùng GV)';
                $lichGvSkip[] = $record;
            } elseif (LichExcelNoiDungSkip::isGvSkip(
                (string) ($row['noi_dung'] ?? ''),
                (string) ($row['chi_tiet'] ?? '')
            )) {
                $record['_skip_reason'] = LichExcelNoiDungSkip::gvSkipLabel();
                $lichGvSkip[] = $record;
            } else {
                $record['_action'] = 'insert';
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

            if ($conflict && $updateModeXe) {
                $existing = $this->findXeLichForUpdate($row['MaGV'], $bienSo, $row['NgayBD'], $row['NgayKT']);
                if ($existing) {
                    $record['MaLichSD'] = $existing->MaLichSD;
                    $record['_action'] = 'update';
                    $lichXe[] = $record;
                    $lichXeUpdate[] = $record;

                    continue;
                }

                $record['_skip_reason'] = 'Trùng lịch nhưng không tìm thấy bản ghi để cập nhật';
                $lichXeSkip[] = $record;
            } elseif ($conflict) {
                $record['_skip_reason'] = 'Đã thêm vào lịch (trùng GV/xe)';
                $lichXeSkip[] = $record;
            } elseif (LichExcelNoiDungSkip::isXeSkip(
                (string) ($row['noi_dung'] ?? ''),
                (string) ($row['chi_tiet'] ?? '')
            )) {
                $record['_skip_reason'] = LichExcelNoiDungSkip::xeSkipLabel(
                    (string) ($row['noi_dung'] ?? ''),
                    (string) ($row['chi_tiet'] ?? '')
                );
                $lichXeSkip[] = $record;
            } elseif ($bienSo === '') {
                $record['_skip_reason'] = 'Chưa gắn xe';
                $lichXeSkip[] = $record;
            } else {
                $record['_action'] = 'insert';
                $lichXe[] = $record;
            }
        }

        return [
            'lich_giao_vien' => $lichGv,
            'lich_giao_vien_bo_qua' => $lichGvSkip,
            'lich_giao_vien_cap_nhat' => $lichGvUpdate,
            'lich_xe_tap' => $lichXe,
            'lich_xe_tap_bo_qua' => $lichXeSkip,
            'lich_xe_tap_cap_nhat' => $lichXeUpdate,
            'meta' => [
                'gv_save' => count(array_filter($lichGv, fn ($r) => ($r['_action'] ?? '') === 'insert')),
                'gv_update' => count($lichGvUpdate),
                'gv_skip' => count($lichGvSkip),
                'xe_save' => count(array_filter($lichXe, fn ($r) => ($r['_action'] ?? '') === 'insert')),
                'xe_update' => count($lichXeUpdate),
                'xe_skip' => count($lichXeSkip),
                'update_mode' => $updateModeGv,
                'update_mode_xe' => $updateModeXe,
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

        $defaultMaMonHoc = LichGvMonHoc::normalizeMa($defaultMon->MaMH ?? null);
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
                        'TenMonHoc' => $defaultMaMonHoc !== null ? (string) $defaultMaMonHoc : '',
                        'MaMonHoc' => $defaultMaMonHoc,
                        'DiaDiem' => '',
                        'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                        'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                        'noi_dung' => $noiDung,
                        'chi_tiet' => $chiTiet,
                        'bien_so_xe' => $bienSo,
                        'skip_save' => LichExcelNoiDungSkip::isGvSkip($noiDung, $chiTiet),
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
                        'skip_save' => LichExcelNoiDungSkip::isXeSkip($noiDung, $chiTiet),
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

                // Ngày nghỉ: cột Nội dung và Chi tiết (ô 3 + 4 trong khối 5 cột/GV) đều trống
                $isOff = trim($noiDung) === '' && trim($chiTiet) === '';
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

    /** @return array<string, string> */
    private function gvRowValidationMessages(): array
    {
        return [
            'rows.*.MaKH.required' => 'Mã khóa học không được để trống.',
            'rows.*.MaGV.required' => 'Mã giáo viên không được để trống.',
            'rows.*.TenGV.required' => 'Tên giáo viên không được để trống.',
            'rows.*.MaMonHoc.required' => 'Vui lòng chọn môn học.',
            'rows.*.NgayBD.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'rows.*.NgayKT.required' => 'Vui lòng chọn thời gian kết thúc.',
        ];
    }

    /** @return array<string, string> */
    private function xeRowValidationMessages(): array
    {
        return [
            'rows.*.MaKH.required' => 'Mã khóa học không được để trống.',
            'rows.*.MaGV.required' => 'Mã giáo viên không được để trống.',
            'rows.*.TenGV.required' => 'Tên giáo viên không được để trống.',
            'rows.*.NgayBD.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'rows.*.NgayKT.required' => 'Vui lòng chọn thời gian kết thúc.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildGvRowsFromSubmission(array $save, array $rows): array
    {
        $gvRows = [];
        foreach ($rows as $i => $row) {
            $ngayBD = Carbon::parse($row['NgayBD']);
            $ngayKT = Carbon::parse($row['NgayKT']);
            if ($ngayKT->lte($ngayBD)) {
                $ngayKT = $ngayBD->copy()->addDay()->setTimeFromTimeString($ngayKT->format('H:i:s'));
            }

            $sourceKey = (string) ($row['source_key'] ?? '');
            $old = $save['gv_rows'][$i] ?? [];
            $maMonHoc = LichGvMonHoc::normalizeMa($row['MaMonHoc']);
            $dbMon = LichGvMonHoc::dbFields($maMonHoc);

            $noiDung = (string) ($old['noi_dung'] ?? '');
            $chiTiet = (string) ($old['chi_tiet'] ?? '');
            $skipGv = LichExcelNoiDungSkip::isGvSkip($noiDung, $chiTiet);

            $gvRows[] = [
                'MaKH' => trim($row['MaKH']),
                'MaGV' => trim($row['MaGV']),
                'TenGV' => trim($row['TenGV']),
                'MaMonHoc' => $maMonHoc,
                'TenMonHoc' => $dbMon['TenMonHoc'],
                'DiaDiem' => '',
                'NgayBD' => $ngayBD->format('Y-m-d H:i:s'),
                'NgayKT' => $ngayKT->format('Y-m-d H:i:s'),
                'noi_dung' => $noiDung,
                'chi_tiet' => $chiTiet,
                'bien_so_xe' => $old['bien_so_xe'] ?? '',
                'source_key' => $sourceKey !== '' ? $sourceKey : ($old['source_key'] ?? (string) $i),
                'skip_save' => $skipGv,
                'conflict' => false,
                'ghi_chu' => '',
            ];
        }

        return $gvRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $gvRows
     * @return array<int, array<string, mixed>>
     */
    private function buildXeRowsFromGvRows(array $save, array $gvRows): array
    {
        $xeByKey = collect($save['xe_rows'] ?? [])->keyBy('source_key');
        $xeRows = [];
        foreach ($gvRows as $gv) {
            $key = $gv['source_key'];
            $oldXe = $xeByKey->get($key, []);
            $noiDung = (string) ($gv['noi_dung'] ?? '');
            $chiTiet = (string) ($gv['chi_tiet'] ?? '');
            $diaDiem = (string) ($oldXe['DiaDiem'] ?? LichExcelDiaDiem::resolve($noiDung));
            $bienSo = LichExcelBienSo::normalize((string) ($oldXe['BienSoXe'] ?? $gv['bien_so_xe'] ?? ''));
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
                'skip_save' => LichExcelNoiDungSkip::isXeSkip($noiDung, $chiTiet),
                'source_key' => $key,
                'conflict' => false,
                'ghi_chu' => '',
            ];
        }

        return $xeRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{gv_rows: array<int, array<string, mixed>>, xe_rows: array<int, array<string, mixed>>}|array{error: string}
     */
    private function buildXeRowsFromDbSubmission(array $save, array $rows): array
    {
        $xeRows = [];
        $oldXeByKey = collect($save['xe_rows'] ?? [])->keyBy('source_key');
        foreach ($rows as $i => $row) {
            $sourceKey = (string) ($row['source_key'] ?? '');
            $old = $oldXeByKey->get($sourceKey, $save['xe_rows'][$i] ?? []);
            $noiDung = (string) ($old['noi_dung'] ?? '');
            $chiTiet = (string) ($old['chi_tiet'] ?? '');
            $skipCabin = LichExcelNoiDungSkip::isXeSkip($noiDung, $chiTiet);

            if (! $skipCabin) {
                if (trim((string) ($row['BienSoXe'] ?? '')) === '') {
                    return ['error' => 'Vui lòng chọn xe tập cho các dòng không bị bỏ qua.'];
                }
                if (trim((string) ($row['DiaDiem'] ?? '')) === '') {
                    return ['error' => 'Vui lòng chọn địa điểm cho các dòng không bị bỏ qua.'];
                }
            }

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
                'noi_dung' => $noiDung,
                'chi_tiet' => $chiTiet,
                'skip_save' => $skipCabin,
                'source_key' => $sourceKey,
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

        return ['gv_rows' => $gvRows, 'xe_rows' => $xeRows];
    }

    private function submitGvChunked(Request $request, array $save): JsonResponse
    {
        try {
            $rows = json_decode((string) $request->input('rows_json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu chunk không hợp lệ.'], 422);
        }

        if (! is_array($rows)) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu chunk không hợp lệ.'], 422);
        }

        try {
            $validated = validator([
                'rows' => $rows,
            ], [
                'rows' => ['required', 'array', 'min:1'],
                'rows.*.MaKH' => ['required', 'string', 'max:50'],
                'rows.*.MaGV' => ['required', 'string', 'max:20'],
                'rows.*.TenGV' => ['required', 'string', 'max:255'],
                'rows.*.MaMonHoc' => ['required', 'integer'],
                'rows.*.NgayBD' => ['required', 'date'],
                'rows.*.NgayKT' => ['required', 'date'],
                'rows.*.source_key' => ['nullable', 'string'],
            ], $this->gvRowValidationMessages())->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        }

        $chunkIndex = max(0, (int) $request->input('chunk_index', 0));
        $chunkTotal = max(1, (int) $request->input('chunk_total', 1));
        $offset = $chunkIndex * self::SUBMIT_CHUNK_SIZE;

        if ($chunkIndex === 0) {
            unset($save['gv_chunk_merge']);
            $save['meta']['update_mode'] = $request->boolean('che_do_cap_nhat');
        }

        $merge = $save['gv_chunk_merge'] ?? [];
        foreach ($validated['rows'] as $j => $row) {
            $merge[$offset + $j] = $row;
        }
        $save['gv_chunk_merge'] = $merge;

        if (! $request->boolean('chunk_finalize')) {
            $request->session()->put(self::SESSION_SAVE, $save);

            return response()->json([
                'ok' => true,
                'chunk_index' => $chunkIndex,
                'chunk_total' => $chunkTotal,
                'merged' => count($merge),
            ]);
        }

        $expected = count($save['gv_rows']);
        if (count($merge) !== $expected) {
            return response()->json([
                'ok' => false,
                'message' => 'Thiếu dữ liệu sau khi gom chunk ('.count($merge).'/'.$expected.').',
            ], 422);
        }

        ksort($merge);
        unset($save['gv_chunk_merge']);

        $gvRows = $this->buildGvRowsFromSubmission($save, array_values($merge));
        $xeRows = $this->buildXeRowsFromGvRows($save, $gvRows);

        $save['gv_rows'] = $gvRows;
        $save['xe_rows'] = $xeRows;
        $request->session()->put(self::SESSION_SAVE, $save);

        return response()->json([
            'ok' => true,
            'redirect' => route('pmgplx.lich.nhap-file.preview-xe'),
        ]);
    }

    private function submitXeChunked(Request $request, array $save): JsonResponse
    {
        try {
            $rows = json_decode((string) $request->input('rows_json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu chunk không hợp lệ.'], 422);
        }

        if (! is_array($rows)) {
            return response()->json(['ok' => false, 'message' => 'Dữ liệu chunk không hợp lệ.'], 422);
        }

        try {
            $validated = validator([
                'rows' => $rows,
            ], [
                'rows' => ['required', 'array', 'min:1'],
                'rows.*.MaKH' => ['required', 'string', 'max:50'],
                'rows.*.MaGV' => ['required', 'string', 'max:20'],
                'rows.*.TenGV' => ['required', 'string', 'max:255'],
                'rows.*.BienSoXe' => ['nullable', 'string', 'max:50'],
                'rows.*.DiaDiem' => ['nullable', 'string', 'max:255'],
                'rows.*.NgayBD' => ['required', 'date'],
                'rows.*.NgayKT' => ['required', 'date'],
                'rows.*.source_key' => ['nullable', 'string'],
            ], $this->xeRowValidationMessages())->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        }

        $chunkIndex = max(0, (int) $request->input('chunk_index', 0));
        $chunkTotal = max(1, (int) $request->input('chunk_total', 1));
        $offset = $chunkIndex * self::SUBMIT_CHUNK_SIZE;

        if ($chunkIndex === 0) {
            unset($save['xe_chunk_merge']);
            $save['meta']['update_mode_xe'] = $request->boolean('che_do_cap_nhat');
        }

        $merge = $save['xe_chunk_merge'] ?? [];
        foreach ($validated['rows'] as $j => $row) {
            $merge[$offset + $j] = $row;
        }
        $save['xe_chunk_merge'] = $merge;

        if (! $request->boolean('chunk_finalize')) {
            $request->session()->put(self::SESSION_SAVE, $save);

            return response()->json([
                'ok' => true,
                'chunk_index' => $chunkIndex,
                'chunk_total' => $chunkTotal,
                'merged' => count($merge),
            ]);
        }

        $expected = count($save['xe_rows']);
        if (count($merge) !== $expected) {
            return response()->json([
                'ok' => false,
                'message' => 'Thiếu dữ liệu sau khi gom chunk ('.count($merge).'/'.$expected.').',
            ], 422);
        }

        ksort($merge);
        unset($save['xe_chunk_merge']);

        $built = $this->buildXeRowsFromDbSubmission($save, array_values($merge));
        if (isset($built['error'])) {
            return response()->json(['ok' => false, 'message' => $built['error']], 422);
        }

        $save['gv_rows'] = $built['gv_rows'];
        $save['xe_rows'] = $built['xe_rows'];
        $save['db_payload'] = $this->buildDbPayload(
            $built['gv_rows'],
            $built['xe_rows'],
            ! empty($save['meta']['update_mode']),
            ! empty($save['meta']['update_mode_xe'])
        );
        $request->session()->put(self::SESSION_SAVE, $save);

        return response()->json([
            'ok' => true,
            'redirect' => route('pmgplx.lich.nhap-file.preview-db'),
        ]);
    }

    private function gvConflict(string $maGv, string $ngayBD, string $ngayKT): bool
    {
        return $this->findGvLichForUpdate($maGv, $ngayBD, $ngayKT) !== null;
    }

    private function findGvLichForUpdate(string $maGv, string $ngayBD, string $ngayKT): ?KhoaHocGiaoVien
    {
        $bd = Carbon::parse($ngayBD);
        $kt = Carbon::parse($ngayKT);

        return KhoaHocGiaoVien::query()
            ->where('MaGV', $maGv)
            ->where('IsKhoaHocGiaoVien', 0)
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd)
            ->orderBy('MaLichLV')
            ->first();
    }

    private function xeConflict(string $maGv, string $bienSoXe, string $ngayBD, string $ngayKT): bool
    {
        return $this->findXeLichForUpdate($maGv, $bienSoXe, $ngayBD, $ngayKT) !== null;
    }

    private function findXeLichForUpdate(string $maGv, string $bienSoXe, string $ngayBD, string $ngayKT): ?KhoaHocXeTap
    {
        $bd = Carbon::parse($ngayBD);
        $kt = Carbon::parse($ngayKT);
        $bienSoXe = trim($bienSoXe);

        $query = KhoaHocXeTap::query()
            ->where('NgayBD', '<', $kt)
            ->where('NgayKT', '>', $bd);

        if ($bienSoXe !== '') {
            $query->where(function ($q) use ($maGv, $bienSoXe) {
                $q->where('MaGV', $maGv)->orWhere('BienSoXe', $bienSoXe);
            });
        } else {
            $query->where('MaGV', $maGv);
        }

        return $query->orderBy('MaLichSD')->first();
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

        if (preg_match('/^(.*?)\s*[-–]?\s*\n\s*([A-Z0-9\-]+(?:\.\d+)?)\s*$/iu', $raw, $m)
            && LichExcelBienSo::isLikelyPlate(trim($m[2]))) {
            $ten = trim($m[1]);
            $ten = (string) preg_replace('/[-–\s]+$/u', '', $ten);

            return [$ten, trim($m[2])];
        }

        if (preg_match('/^(.*?)\s*[-–]\s*([A-Z0-9\-]+(?:\.\d+)?)\s*$/iu', $raw, $m)
            && LichExcelBienSo::isLikelyPlate(trim($m[2]))) {
            return [trim($m[1]), trim($m[2])];
        }

        $parts = array_values(array_filter(
            array_map('trim', preg_split("/\n+/", $raw) ?: [$raw]),
            static fn ($part) => $part !== ''
        ));

        if (count($parts) >= 2 && LichExcelBienSo::isLikelyPlate($parts[count($parts) - 1])) {
            $bienSo = array_pop($parts);

            return [trim($this->joinNameLines($parts)), $bienSo];
        }

        return [$this->joinNameLines($parts !== [] ? $parts : [$raw]), ''];
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinNameLines(array $parts): string
    {
        if ($parts === []) {
            return '';
        }

        $name = trim($parts[0]);
        for ($i = 1; $i < count($parts); $i++) {
            $chunk = trim($parts[$i]);
            if ($chunk === '') {
                continue;
            }

            // Tên bị xuống dòng giữa chữ (vd. "HOÀNG TRUNG H" + "À" → "HOÀNG TRUNG HÀ")
            if (mb_strlen($chunk) <= 2
                && ! str_contains($chunk, ' ')
                && preg_match('/\p{L}$/u', $name)) {
                $name .= $chunk;
            } else {
                $name .= ' '.$chunk;
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
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
