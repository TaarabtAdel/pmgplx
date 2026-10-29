<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienDocxExporter;
use App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuyetDinhDoiGiaoVienController extends Controller
{
    private const SLUG = '2-quyet-dinh-doi-giao-vien';

    private const SESSION_KEY = 'qldtoto.preview.quyet_dinh_doi_giao_vien';

    private const ROW_FIELDS = ['gv_cu', 'xe', 'tu_cu', 'den_cu', 'gv_moi', 'tu_moi', 'den_moi'];

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $form = [
            'so_qd' => '',
            'ngay_quyet_dinh' => now()->format('Y-m-d'),
            'khoa_list' => [],
        ];
        $session = $request->session()->get(self::SESSION_KEY);
        if (is_array($session) && is_array($session['form'] ?? null)) {
            $form = array_merge($form, $session['form']);
        }

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.quyet-dinh-doi-giao-vien.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'giaoVienOptions' => PhanCongGvXeTapLaiFormSupport::giaoVienSelectOptions(),
            'xeTapOptions' => PhanCongGvXeTapLaiFormSupport::xeSelectOptions(),
            'form' => $form,
        ]);
    }

    public function khoaJson(string $maKh): JsonResponse
    {
        $khoa = KhoaHoc::query()->find($maKh);
        if (! $khoa instanceof KhoaHoc) {
            return response()->json(['message' => 'Không tìm thấy khoá.'], 404);
        }

        return response()->json([
            'ma_kh' => (string) $khoa->MaKH,
            'ten_ngan' => QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa),
            'ngay_bg' => $khoa->NgayBG ? Carbon::parse($khoa->NgayBG)->format('Y-m-d') : '',
            'rows' => QuyetDinhDoiGiaoVienSupport::goiYDong($khoa),
        ]);
    }

    public function previewStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'so_qd' => ['required', 'regex:/^\d+$/', 'max:10'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'khoa_list' => ['required', 'array', 'min:1'],
            'khoa_list.*.ma_kh' => ['required', 'string', 'max:50'],
            'khoa_list.*.ten_ngan' => ['required', 'string', 'max:40'],
            'khoa_list.*.ngay_bg' => ['nullable', 'date'],
            'khoa_list.*.rows' => ['nullable', 'array'],
            'khoa_list.*.rows.*.gv_cu' => ['nullable', 'string', 'max:20'],
            'khoa_list.*.rows.*.xe' => ['nullable', 'string', 'max:30'],
            'khoa_list.*.rows.*.tu_cu' => ['nullable', 'date'],
            'khoa_list.*.rows.*.den_cu' => ['nullable', 'date'],
            'khoa_list.*.rows.*.gv_moi' => ['nullable', 'string', 'max:20'],
            'khoa_list.*.rows.*.tu_moi' => ['nullable', 'date'],
            'khoa_list.*.rows.*.den_moi' => ['nullable', 'date'],
        ], [
            'so_qd.regex' => 'Số quyết định chỉ gồm chữ số.',
        ]);

        $formDraft = [
            'so_qd' => $validated['so_qd'],
            'ngay_quyet_dinh' => $validated['ngay_quyet_dinh'],
            'khoa_list' => [],
        ];
        $khoaList = [];
        $errors = [];

        foreach (array_values($validated['khoa_list']) as $khoaInput) {
            $tenNgan = trim($khoaInput['ten_ngan']);
            $draftRows = [];
            $rows = [];

            foreach (array_values($khoaInput['rows'] ?? []) as $i => $rowInput) {
                $row = [];
                foreach (self::ROW_FIELDS as $f) {
                    $row[$f] = trim((string) ($rowInput[$f] ?? ''));
                }
                if (implode('', $row) === '') {
                    continue;
                }
                $draftRows[] = $row;

                $label = 'Khoá '.$tenNgan.', dòng '.($i + 1);
                foreach (self::ROW_FIELDS as $f) {
                    if ($row[$f] === '') {
                        $errors[] = $label.': nhập đủ GV cũ, xe, thời gian và GV thay.';
                        continue 2;
                    }
                }
                if ($row['gv_cu'] === $row['gv_moi']) {
                    $errors[] = $label.': giáo viên thay trùng giáo viên cũ.';
                }
                if ($row['tu_cu'] > $row['den_cu'] || $row['tu_moi'] > $row['den_moi']) {
                    $errors[] = $label.': ngày bắt đầu sau ngày kết thúc.';
                }
                if ($row['tu_moi'] <= $row['den_cu']) {
                    $errors[] = $label.': giáo viên thay phải bắt đầu sau ngày giáo viên cũ kết thúc.';
                }

                $rows[] = $row + [
                    'gv_cu_ten' => QuyetDinhDoiGiaoVienSupport::hoTenGiaoVien($row['gv_cu']),
                    'gv_moi_ten' => QuyetDinhDoiGiaoVienSupport::hoTenGiaoVien($row['gv_moi']),
                    'xe' => PhanCongGvXeTapLaiFormSupport::bienSoTuDanhMucXe($row['xe']),
                ];
            }

            $formDraft['khoa_list'][] = [
                'ma_kh' => $khoaInput['ma_kh'],
                'ten_ngan' => $tenNgan,
                'ngay_bg' => (string) ($khoaInput['ngay_bg'] ?? ''),
                'rows' => $draftRows,
            ];

            if ($rows === []) {
                $errors[] = 'Khoá '.$tenNgan.': thêm ít nhất một dòng đổi giáo viên.';

                continue;
            }
            $khoaList[] = ['ma_kh' => $khoaInput['ma_kh'], 'ten_ngan' => $tenNgan, 'rows' => $rows];
        }

        $request->session()->put(self::SESSION_KEY, ['form' => $formDraft]);

        if ($errors !== []) {
            return redirect()
                ->route('qldtoto.quyet-dinh-doi-giao-vien.index')
                ->withErrors(['khoa_list' => array_values(array_unique($errors))]);
        }

        $request->session()->put(self::SESSION_KEY, [
            'form' => $formDraft,
            'preview' => [
                'so_qd' => $validated['so_qd'],
                'ngay_quyet_dinh' => Carbon::parse($validated['ngay_quyet_dinh'])->format('Y-m-d'),
                'khoa_list' => $khoaList,
            ],
        ]);

        return redirect()->route('qldtoto.quyet-dinh-doi-giao-vien.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY.'.preview');
        if (! is_array($preview) || empty($preview['khoa_list'])) {
            return redirect()
                ->route('qldtoto.quyet-dinh-doi-giao-vien.index')
                ->with('error', 'Chưa có dữ liệu xem trước.');
        }

        return view('QldtOto.quyet-dinh-doi-giao-vien.preview', ['preview' => $preview]);
    }

    public function export(Request $request, QuyetDinhDoiGiaoVienDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY.'.preview');
        if (! is_array($preview) || empty($preview['khoa_list'])) {
            return redirect()
                ->route('qldtoto.quyet-dinh-doi-giao-vien.index')
                ->with('error', 'Chưa có dữ liệu xem trước để xuất Word.');
        }

        $tmp = storage_path('app/tmp/quyet-dinh-doi-giao-vien-'.uniqid('', true).'.docx');
        try {
            $exporter->generate($preview, $tmp);
        } catch (\Throwable $e) {
            return redirect()
                ->route('qldtoto.quyet-dinh-doi-giao-vien.preview')
                ->with('error', 'Xuất Word thất bại: '.$e->getMessage());
        }

        return response()
            ->download($tmp, 'quyet-dinh-doi-giao-vien-'.$preview['so_qd'].'.docx')
            ->deleteFileAfterSend(true);
    }

    public function cancel(): RedirectResponse
    {
        return redirect()->route('qldtoto.quyet-dinh-doi-giao-vien.index');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('qldtoto.quyet-dinh-doi-giao-vien.index');
    }
}
