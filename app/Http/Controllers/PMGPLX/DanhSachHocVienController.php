<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Models\PMGPLX\NguoiLXHoSo;
use App\Models\PMGPLXOLD\KhoaHoc as KhoaHocOld;
use App\Services\PMGPLX\DongBoHocVienBanCuService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DanhSachHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $items = $this->filteredQuery($request)
            ->orderByDesc('n.NgayTao')
            ->orderBy('n.MaDK')
            ->paginate($perPage)
            ->withQueryString();

        $khoaHocs = KhoaHoc::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        return view('PMGPLX.danh-muc.hoc-vien', [
            'items' => $items,
            'khoaHocs' => $khoaHocs,
            'filters' => [
                'tu_khoa' => $request->input('tu_khoa', ''),
                'ma_kh' => $request->input('ma_kh', ''),
                'hang_gplx' => $request->input('hang_gplx', ''),
                'trang_thai' => $request->input('trang_thai', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function dongBoForm(Request $request, DongBoHocVienBanCuService $service): View
    {
        $maKhNguon = trim((string) $request->input('ma_kh_nguon', ''));
        $maKhDich = trim((string) $request->input('ma_kh_dich', ''));

        $khoaHocsNguon = KhoaHoc::query()
            ->whereIn('MaKH', function ($q) {
                $q->select('MaKhoaHoc')->from('NguoiLX_HoSo')->whereNotNull('MaKhoaHoc');
            })
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        $khoaHocsDich = KhoaHocOld::query()
            ->orderBy('TenKH')
            ->get(['MaKH', 'TenKH']);

        $preview = null;
        if ($maKhNguon !== '') {
            $preview = $service->buildPreview($maKhNguon, $maKhDich !== '' ? $maKhDich : null);
        }

        return view('PMGPLX.danh-muc.hoc-vien-dong-bo', [
            'khoaHocsNguon' => $khoaHocsNguon,
            'khoaHocsDich' => $khoaHocsDich,
            'maKhNguon' => $maKhNguon,
            'maKhDich' => $maKhDich,
            'preview' => $preview,
            'lastBatch' => $request->session()->get(DongBoHocVienBanCuService::SESSION_LAST_BATCH),
        ]);
    }

    public function dongBoStore(Request $request, DongBoHocVienBanCuService $service): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh_nguon' => ['required', 'string'],
            'ma_kh_dich' => ['required', 'string'],
        ], [
            'ma_kh_nguon.required' => 'Chọn mã khóa học nguồn (phần mềm mới).',
            'ma_kh_dich.required' => 'Chọn mã khóa học đích (phần mềm cũ).',
        ]);

        $maKhNguon = trim((string) $validated['ma_kh_nguon']);
        $maKhDich = trim((string) $validated['ma_kh_dich']);

        if (! KhoaHoc::query()->where('MaKH', $maKhNguon)->exists()) {
            return back()->withInput()->with('error', 'Mã khóa học nguồn không tồn tại.');
        }

        try {
            $result = $service->sync($maKhNguon, $maKhDich);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Đồng bộ thất bại: '.$e->getMessage());
        }

        if (($result['inserted'] ?? 0) + ($result['updated'] ?? 0) === 0) {
            $message = $result['failed'] > 0
                ? "Không có học viên nào được đồng bộ — {$result['failed']} lỗi."
                : 'Không có học viên nào được đồng bộ.';

            return redirect()
                ->route('pmgplx.dm.hoc-vien.dong-bo.form', [
                    'ma_kh_nguon' => $maKhNguon,
                    'ma_kh_dich' => $maKhDich,
                ])
                ->with('error', $message);
        }

        $request->session()->put(
            DongBoHocVienBanCuService::SESSION_LAST_BATCH,
            $service->buildSessionBatch($maKhNguon, $maKhDich, $result)
        );

        $synced = ($result['inserted'] ?? 0) + ($result['updated'] ?? 0);
        $detail = [];
        if (($result['inserted'] ?? 0) > 0) {
            $detail[] = "thêm mới {$result['inserted']}";
        }
        if (($result['updated'] ?? 0) > 0) {
            $detail[] = "cập nhật {$result['updated']}";
        }
        if (($result['failed'] ?? 0) > 0) {
            $detail[] = "lỗi {$result['failed']}";
        }

        $message = "Đã đồng bộ {$synced} học viên từ khóa {$maKhNguon} sang khóa {$maKhDich} trên bản cũ";
        if ($detail !== []) {
            $message .= ' ('.implode(', ', $detail).')';
        }
        $message .= '. Bao gồm NguoiLX, NguoiLX_HoSo và NguoiLXHS_GiayTo. Có thể dùng Khôi phục để xóa lại trên bản cũ.';

        return redirect()
            ->route('pmgplx.dm.hoc-vien.dong-bo.form', [
                'ma_kh_nguon' => $maKhNguon,
                'ma_kh_dich' => $maKhDich,
            ])
            ->with('success', $message);
    }

    public function dongBoTestMot(Request $request, DongBoHocVienBanCuService $service): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh_nguon' => ['required', 'string'],
            'ma_kh_dich' => ['required', 'string'],
            'ma_dk_nguon' => ['required', 'string'],
        ], [
            'ma_kh_nguon.required' => 'Chọn mã khóa học nguồn (phần mềm mới).',
            'ma_kh_dich.required' => 'Chọn mã khóa học đích (phần mềm cũ).',
            'ma_dk_nguon.required' => 'Không xác định được học viên để test.',
        ]);

        $maKhNguon = trim((string) $validated['ma_kh_nguon']);
        $maKhDich = trim((string) $validated['ma_kh_dich']);
        $maDkNguon = trim((string) $validated['ma_dk_nguon']);

        if (! KhoaHoc::query()->where('MaKH', $maKhNguon)->exists()) {
            return back()->withInput()->with('error', 'Mã khóa học nguồn không tồn tại.');
        }

        $preview = $service->buildPreview($maKhNguon, $maKhDich);
        $testStudent = $preview['meta']['test_student'] ?? null;

        if (! is_array($testStudent) || ($testStudent['ma_dk_nguon'] ?? '') !== $maDkNguon) {
            return back()->withInput()->with('error', 'Học viên test không hợp lệ.');
        }

        try {
            $result = $service->sync($maKhNguon, $maKhDich, $maDkNguon);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Test đồng bộ thất bại: '.$e->getMessage());
        }

        if (($result['inserted'] ?? 0) + ($result['updated'] ?? 0) === 0) {
            return redirect()
                ->route('pmgplx.dm.hoc-vien.dong-bo.form', [
                    'ma_kh_nguon' => $maKhNguon,
                    'ma_kh_dich' => $maKhDich,
                ])
                ->with('error', 'Không thể test đồng bộ học viên này.');
        }

        $request->session()->put(
            DongBoHocVienBanCuService::SESSION_LAST_BATCH,
            $service->buildSessionBatch($maKhNguon, $maKhDich, $result, true)
        );

        $hoTen = trim((string) ($result['ho_ten'] ?? $testStudent['ho_ten'] ?? ''));
        $targetMaDk = $result['target_ma_dk'] ?? $testStudent['ma_dk'] ?? '';
        $action = ($result['updated'] ?? 0) > 0 && ($result['inserted'] ?? 0) === 0 ? 'cập nhật' : 'đồng bộ';

        return redirect()
            ->route('pmgplx.dm.hoc-vien.dong-bo.form', [
                'ma_kh_nguon' => $maKhNguon,
                'ma_kh_dich' => $maKhDich,
            ])
            ->with(
                'success',
                "Đã test {$action} 1 học viên ({$hoTen}): {$maDkNguon} → {$targetMaDk} trên bản cũ (kèm giấy tờ). Kiểm tra dữ liệu, sau đó dùng Khôi phục nếu cần xóa."
            );
    }

    public function dongBoKhoiPhuc(Request $request, DongBoHocVienBanCuService $service): RedirectResponse
    {
        $batch = $request->session()->get(DongBoHocVienBanCuService::SESSION_LAST_BATCH);

        if (! is_array($batch) || empty($batch['ma_dks'])) {
            return redirect()
                ->route('pmgplx.dm.hoc-vien.dong-bo.form')
                ->with('error', 'Không có lần đồng bộ gần đây để khôi phục.');
        }

        try {
            $result = $service->restore($batch);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Khôi phục thất bại: '.$e->getMessage());
        }

        $request->session()->forget(DongBoHocVienBanCuService::SESSION_LAST_BATCH);

        return redirect()
            ->route('pmgplx.dm.hoc-vien.dong-bo.form', [
                'ma_kh_nguon' => $batch['ma_kh_nguon'] ?? '',
                'ma_kh_dich' => $batch['ma_kh_dich'] ?? '',
            ])
            ->with(
                'success',
                "Đã khôi phục: xóa {$result['deleted']} / {$result['total']} học viên khỏi bản cũ (NguoiLX, hồ sơ, giấy tờ — khóa {$batch['ma_kh_dich']})."
            );
    }

    /**
     * Đồng bộ toàn bộ kết quả theo form tìm kiếm (không phân trang) sang DB bản cũ.
     */
    public function dongBo(Request $request): RedirectResponse
    {
        $maDKs = $this->filteredQuery($request)
            ->orderBy('n.MaDK')
            ->pluck('n.MaDK')
            ->unique()
            ->values()
            ->all();

        if ($maDKs === []) {
            return back()->with('error', 'Không có học viên nào theo bộ lọc để đồng bộ.');
        }

        [$colsNguoi, $colsHoSo, $colsGiayTo] = app(DongBoHocVienBanCuService::class)->mappableColumns();

        $inserted = 0;
        $updated = 0;
        $failed = 0;

        $nguoiRows = NguoiLX::query()->whereIn('MaDK', $maDKs)->get()->keyBy('MaDK');
        $hoSoRows = NguoiLXHoSo::query()->whereIn('MaDK', $maDKs)->get()->keyBy('MaDK');
        $giayToRows = DB::connection('sqlsrv')
            ->table('NguoiLXHS_GiayTo')
            ->whereIn('MaDK', $maDKs)
            ->get()
            ->groupBy('MaDK');

        $oldDb = DB::connection('sqlsrv_old');

        try {
            $oldDb->beginTransaction();

            foreach ($maDKs as $maDK) {
                $nguoi = $nguoiRows->get($maDK);
                if (! $nguoi) {
                    $failed++;
                    continue;
                }

                $payloadNguoi = $this->onlyColumns($nguoi->getAttributes(), $colsNguoi);
                $exists = $oldDb->table('NguoiLX')->where('MaDK', $maDK)->exists();
                $oldDb->table('NguoiLX')->updateOrInsert(['MaDK' => $maDK], $payloadNguoi);
                $exists ? $updated++ : $inserted++;

                $hoSo = $hoSoRows->get($maDK);
                if ($hoSo) {
                    $payloadHoSo = $this->onlyColumns($hoSo->getAttributes(), $colsHoSo);
                    $oldDb->table('NguoiLX_HoSo')->updateOrInsert(['MaDK' => $maDK], $payloadHoSo);
                }

                foreach ($giayToRows->get($maDK, collect()) as $giayTo) {
                    $payloadGiayTo = $this->onlyColumns((array) $giayTo, $colsGiayTo);
                    $maGt = $payloadGiayTo['MaGT'] ?? null;
                    if ($maGt === null) {
                        continue;
                    }
                    $oldDb->table('NguoiLXHS_GiayTo')->updateOrInsert(
                        ['MaDK' => $maDK, 'MaGT' => $maGt],
                        $payloadGiayTo
                    );
                }
            }

            $oldDb->commit();
        } catch (\Throwable $e) {
            $oldDb->rollBack();

            return back()->with('error', 'Đồng bộ thất bại: '.$e->getMessage());
        }

        $total = count($maDKs);

        return back()->with(
            'success',
            "Đã đồng bộ {$total} học viên sang bản cũ (thêm mới: {$inserted}, cập nhật: {$updated}".
            ($failed > 0 ? ", lỗi: {$failed}" : '').').'
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->select([
                'n.MaDK',
                'n.HoDemNLX',
                'n.TenNLX',
                'n.HoVaTen',
                'n.NgaySinh',
                'n.GioiTinh',
                'n.SoCMT',
                'n.TrangThai',
                'h.SoHoSo',
                'h.MaKhoaHoc',
                'h.HangGPLX',
                'h.HangDaoTao',
            ]);

        if ($keyword = trim((string) $request->input('tu_khoa'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('n.MaDK', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoVaTen', 'like', '%'.$keyword.'%')
                    ->orWhere('n.HoDemNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.TenNLX', 'like', '%'.$keyword.'%')
                    ->orWhere('n.SoCMT', 'like', '%'.$keyword.'%')
                    ->orWhere('h.SoHoSo', 'like', '%'.$keyword.'%');
            });
        }

        if ($maKH = trim((string) $request->input('ma_kh'))) {
            $query->where('h.MaKhoaHoc', $maKH);
        }

        if ($hang = trim((string) $request->input('hang_gplx'))) {
            $query->where('h.HangGPLX', 'like', '%'.$hang.'%');
        }

        if ($request->filled('trang_thai') && $request->input('trang_thai') !== '') {
            $query->where('n.TrangThai', (string) (int) $request->input('trang_thai'));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function onlyColumns(array $attributes, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (! array_key_exists($col, $attributes)) {
                continue;
            }
            $val = $attributes[$col];
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }
            $out[$col] = $val;
        }

        return $out;
    }
}
