<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\MenuQldt;
use App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport;
use App\Support\QldtOto\QuyetDinhDieuDongXeDocxData;
use App\Support\QldtOto\QuyetDinhDieuDongXeDocxExporter;
use App\Support\QldtOto\QuyetDinhDieuDongXeSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuyetDinhDieuDongXeController extends Controller
{
    private const SLUG = '1-quyet-dinh-dieu-dong-xe-cua-tung-giao-vien';

    private const SESSION_KEY = 'qldtoto.preview.quyet_dinh_dieu_dong_xe';

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang(self::SLUG);
        abort_if($trang === null, 404);

        $maKh = trim((string) $request->query('ma_kh', ''));
        $khoa = $maKh !== '' ? KhoaHoc::query()->find($maKh) : null;

        $khoaDaoTaoId = null;
        $tenKhoaDaoTao = '';
        $soPhanCong = 0;
        $xeBlocks = [];
        $linkDanhSach = QuyetDinhDieuDongXeSupport::urlDanhSachPhanCong(null);
        $trangThaiPhanCong = 'chua_chon_khoa';
        $canhBaoPhanCong = null;

        if ($khoa instanceof KhoaHoc) {
            $tenKhoaDaoTao = PhanCongGvXeTapLaiFormSupport::tenKhoaDaoTaoTuPm($khoa);
            $khoaDt = QuyetDinhDieuDongXeSupport::resolveKhoaDaoTao($khoa);
            if ($khoaDt !== null) {
                $khoaDaoTaoId = (int) $khoaDt->Id;
                $soPhanCong = QuyetDinhDieuDongXeSupport::demPhanCongKhoa($khoaDaoTaoId);
                $linkDanhSach = QuyetDinhDieuDongXeSupport::urlDanhSachPhanCong($khoaDaoTaoId);
                if ($soPhanCong > 0) {
                    $xeBlocks = QuyetDinhDieuDongXeSupport::loadXeBlocksFromPhanCong($khoaDaoTaoId);
                    if ($xeBlocks === []) {
                        $canhBaoPhanCong = 'co_phan_cong_chua_xe';
                    }
                } else {
                    $canhBaoPhanCong = 'chua_phan_cong';
                }
            } else {
                $canhBaoPhanCong = 'chua_phan_cong';
            }

            $trangThaiPhanCong = 'co_du_lieu';
        }

        $ngayQuyetDinh = now()->format('Y-m-d');
        $hangKhoa = $khoa instanceof KhoaHoc ? QuyetDinhDieuDongXeSupport::hangKhoaMacDinh($khoa) : '';

        $sessionPreview = $request->session()->get(self::SESSION_KEY);
        if (is_array($sessionPreview) && ($sessionPreview['ma_kh'] ?? '') === $maKh && ! empty($sessionPreview['xe_blocks'])) {
            $xeBlocks = $sessionPreview['xe_blocks'];
            $ngayQuyetDinh = (string) ($sessionPreview['ngay_quyet_dinh'] ?? $ngayQuyetDinh);
            $hangKhoa = (string) ($sessionPreview['hang_khoa'] ?? $hangKhoa);
        }

        if ($khoa instanceof KhoaHoc && $xeBlocks === []) {
            $xeBlocks = self::defaultXeBlocks();
        }

        $xeThongTin = QuyetDinhDieuDongXeSupport::xeThongTinMap();
        $xeBlocks = QuyetDinhDieuDongXeSupport::boSungThongTinXe($xeBlocks, $xeThongTin);

        $khoaOptions = KhoaHoc::query()
            ->select('MaKH', 'TenKH', 'NgayKG')
            ->orderByDesc('NgayKG')
            ->orderByDesc('MaKH')
            ->limit(500)
            ->get();

        return view('QldtOto.quyet-dinh-dieu-dong-xe.form', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'khoaOptions' => $khoaOptions,
            'giaoVienOptions' => PhanCongGvXeTapLaiFormSupport::giaoVienSelectOptions(),
            'xeTapOptions' => PhanCongGvXeTapLaiFormSupport::xeSelectOptions(),
            'noiDungOptions' => PhanCongGvXeTapLaiFormSupport::NOI_DUNG_OPTIONS,
            'xeThongTin' => $xeThongTin,
            'form' => [
                'ma_kh' => $maKh,
                'ten_khoa' => $tenKhoaDaoTao,
                'ngay_quyet_dinh' => $ngayQuyetDinh,
                'hang_khoa' => $hangKhoa,
                'xe_blocks' => $xeBlocks,
            ],
            'khoaDaoTaoId' => $khoaDaoTaoId,
            'soPhanCong' => $soPhanCong,
            'linkDanhSach' => $linkDanhSach,
            'trangThaiPhanCong' => $trangThaiPhanCong,
            'canhBaoPhanCong' => $canhBaoPhanCong,
        ]);
    }

    /**
     * @return list<array{bien_so: string, giao_vien: list<array<string, string>>}>
     */
    private static function defaultXeBlocks(): array
    {
        return [
            [
                'bien_so' => '',
                'so_quyet_dinh' => '',
                'loai_xe' => '',
                'nhan_hieu' => '',
                'mau_son' => '',
                'giao_vien' => [
                    [
                        'ma_gv' => '',
                        'ho_ten' => '',
                        'tu_ngay' => '',
                        'den_ngay' => '',
                        'noi_dung' => 'GVTH',
                    ],
                ],
            ],
        ];
    }

    public function previewStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ten_khoa' => ['required', 'string', 'max:120'],
            'ngay_quyet_dinh' => ['required', 'date'],
            'hang_khoa' => ['required', 'string', 'max:150'],
            'xe_blocks' => ['required', 'array', 'min:1'],
            'xe_blocks.*.bien_so' => ['nullable', 'string', 'max:30'],
            'xe_blocks.*.so_quyet_dinh' => ['nullable', 'string', 'max:20'],
            'xe_blocks.*.loai_xe' => ['nullable', 'string', 'max:60'],
            'xe_blocks.*.nhan_hieu' => ['nullable', 'string', 'max:60'],
            'xe_blocks.*.mau_son' => ['nullable', 'string', 'max:60'],
            'xe_blocks.*.giao_vien' => ['nullable', 'array'],
            'xe_blocks.*.giao_vien.*.ma_gv' => ['nullable', 'string', 'max:20'],
            'xe_blocks.*.giao_vien.*.ho_ten' => ['nullable', 'string', 'max:120'],
            'xe_blocks.*.giao_vien.*.tu_ngay' => ['nullable', 'string', 'max:20'],
            'xe_blocks.*.giao_vien.*.den_ngay' => ['nullable', 'string', 'max:20'],
            'xe_blocks.*.giao_vien.*.noi_dung' => ['nullable', 'string', 'max:40'],
        ]);

        if (! KhoaHoc::query()->where('MaKH', $validated['ma_kh'])->exists()) {
            return back()->withErrors(['ma_kh' => 'Không tìm thấy khoá trên PMGPLX.'])->withInput();
        }

        $xeBlocks = [];
        foreach (array_values($validated['xe_blocks']) as $block) {
            $bien = PhanCongGvXeTapLaiFormSupport::bienSoTuDanhMucXe(trim((string) ($block['bien_so'] ?? '')));
            $gvRaw = array_values($block['giao_vien'] ?? []);
            $gvRows = [];
            foreach ($gvRaw as $gv) {
                $ma = trim((string) ($gv['ma_gv'] ?? ''));
                $ten = trim((string) ($gv['ho_ten'] ?? ''));
                if ($ma === '' && $ten === '') {
                    continue;
                }
                $gvRows[] = $gv;
            }
            $gvRows = QuyetDinhDieuDongXeSupport::resolveGiaoVienRows($gvRows);
            if ($bien === '' && $gvRows === []) {
                continue;
            }
            if ($bien === '') {
                return back()->withErrors(['xe_blocks' => 'Mỗi nhóm cần chọn biển số xe.'])->withInput();
            }
            if ($gvRows === []) {
                return back()->withErrors(['xe_blocks' => 'Xe '.$bien.': thêm ít nhất một giáo viên.'])->withInput();
            }
            $soQuyetDinh = QuyetDinhDieuDongXeSupport::soQuyetDinhChiSo($block['so_quyet_dinh'] ?? '');
            if ($soQuyetDinh === '') {
                return back()->withErrors(['xe_blocks' => 'Xe '.$bien.': nhập số quyết định (chỉ số).'])->withInput();
            }
            $xeBlocks[] = [
                'bien_so' => $bien,
                'so_quyet_dinh' => $soQuyetDinh,
                'loai_xe' => trim((string) ($block['loai_xe'] ?? '')),
                'nhan_hieu' => trim((string) ($block['nhan_hieu'] ?? '')),
                'mau_son' => trim((string) ($block['mau_son'] ?? '')),
                'giao_vien' => $gvRows,
            ];
        }

        if ($xeBlocks === []) {
            return back()->withErrors(['xe_blocks' => 'Thêm ít nhất một xe và giáo viên.'])->withInput();
        }

        $flat = QuyetDinhDieuDongXeSupport::flattenForPreview($xeBlocks);

        $preview = [
            'ma_kh' => $validated['ma_kh'],
            'ten_khoa' => $validated['ten_khoa'],
            'ngay_quyet_dinh' => \Carbon\Carbon::parse($validated['ngay_quyet_dinh'])->format('Y-m-d'),
            'hang_khoa' => trim($validated['hang_khoa']),
            'xe_blocks' => $xeBlocks,
            'rows' => $flat,
            'meta' => [
                'xe_count' => count($xeBlocks),
                'gv_count' => count($flat),
            ],
        ];

        $request->session()->put(self::SESSION_KEY, $preview);

        return redirect()->route('qldtoto.quyet-dinh-dieu-dong-xe.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['xe_blocks'])) {
            return redirect()
                ->route('qldtoto.quyet-dinh-dieu-dong-xe.index')
                ->with('error', 'Chưa có dữ liệu xem trước.');
        }

        return view('QldtOto.quyet-dinh-dieu-dong-xe.preview', [
            'preview' => $preview,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $maKh = '';
        $preview = $request->session()->get(self::SESSION_KEY);
        if (is_array($preview)) {
            $maKh = (string) ($preview['ma_kh'] ?? '');
        }

        return redirect()->route('qldtoto.quyet-dinh-dieu-dong-xe.index', array_filter(['ma_kh' => $maKh]));
    }

    public function export(Request $request, QuyetDinhDieuDongXeDocxExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! is_array($preview) || empty($preview['xe_blocks'])) {
            return redirect()
                ->route('qldtoto.quyet-dinh-dieu-dong-xe.index')
                ->with('error', 'Chưa có dữ liệu xem trước để xuất Word.');
        }

        $suffix = '';
        if ($request->filled('xe')) {
            $xi = (int) $request->query('xe');
            $block = $preview['xe_blocks'][$xi] ?? null;
            if (! is_array($block)) {
                return redirect()
                    ->route('qldtoto.quyet-dinh-dieu-dong-xe.preview')
                    ->with('error', 'Không tìm thấy xe cần xuất.');
            }
            $preview['xe_blocks'] = [$block];
            $suffix = '-'.(preg_replace('/[^A-Za-z0-9]+/', '', (string) ($block['bien_so'] ?? '')) ?: 'xe'.($xi + 1));
        }

        try {
            $pages = QuyetDinhDieuDongXeDocxData::pagesFromPreview($preview);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('qldtoto.quyet-dinh-dieu-dong-xe.preview')
                ->with('error', $e->getMessage());
        }

        $tmp = storage_path('app/tmp/quyet-dinh-dieu-dong-xe-'.uniqid('', true).'.docx');
        try {
            $exporter->generate($pages, $tmp);
        } catch (\Throwable $e) {
            return redirect()
                ->route('qldtoto.quyet-dinh-dieu-dong-xe.preview')
                ->with('error', 'Xuất Word thất bại: '.$e->getMessage());
        }

        $safeMa = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($preview['ma_kh'] ?? 'khoa')) ?: 'khoa';

        return response()->download(
            $tmp,
            'quyet-dinh-dieu-dong-xe-'.$safeMa.$suffix.'.docx'
        )->deleteFileAfterSend(true);
    }
}
