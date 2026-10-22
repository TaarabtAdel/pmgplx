<?php

namespace App\Http\Controllers\QldtOto\LichDuKien;

use App\Http\Controllers\Controller;
use App\Models\QldtOto\BaiDay;
use App\Models\QldtOto\LichDuKienThucHanh;
use App\Models\QldtOto\LichDuKienThucHanhCapGv;
use App\Models\QldtOto\LichDuKienThucHanhNgayNghi;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\XeTap;
use App\Support\QldtOto\SinhLichDuKienThucHanh;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** QLĐT Ô TÔ → Làm lịch dự kiến → Thực hành: danh sách + tạo/sửa/xoá lịch dự kiến. */
class ThucHanhController extends Controller
{
    /** Hạng GPLX của xe trong danh mục XeTap ứng với xe số tự động. */
    private const HANG_XE_TU_DONG = 'B11';

    public function index(Request $request): View
    {
        $filters = [
            'tu_khoa' => trim((string) $request->query('tu_khoa', '')),
            'hang' => (string) $request->query('hang', ''),
        ];

        $items = LichDuKienThucHanh::query()
            ->with('nguoiTao:Id,Ten')
            ->with('ngayNghis:Id,LichDuKienId,Ngay')
            ->withCount('capGvs')
            ->when($filters['tu_khoa'] !== '', fn ($q) => $q->where('TenKhoa', 'like', '%'.$filters['tu_khoa'].'%'))
            ->when(isset(BaiDay::HANG[$filters['hang']]), fn ($q) => $q->where('Hang', $filters['hang']))
            ->orderByDesc('NgayBatDauThucHanh')
            ->orderByDesc('Id')
            ->paginate(20)
            ->withQueryString();

        return view('QldtOto.lich-du-kien.thuc-hanh.index', [
            'items' => $items,
            'filters' => $filters,
            'hangOptions' => BaiDay::HANG,
        ]);
    }

    public function create(): View
    {
        return $this->form(new LichDuKienThucHanh());
    }

    public function store(Request $request): RedirectResponse
    {
        $lich = new LichDuKienThucHanh(['NguoiTaoId' => auth()->id(), 'NgayTao' => Carbon::now()]);
        $this->luu($request, $lich);

        return $this->sauKhiLuu($request, $lich, 'Đã tạo lịch dự kiến "'.$lich->TenKhoa.'".');
    }

    public function edit(LichDuKienThucHanh $lich): View
    {
        return $this->form($lich);
    }

    public function update(Request $request, LichDuKienThucHanh $lich): RedirectResponse
    {
        $this->luu($request, $lich);

        return $this->sauKhiLuu($request, $lich, 'Đã lưu lịch dự kiến "'.$lich->TenKhoa.'".');
    }

    public function destroy(LichDuKienThucHanh $lich): RedirectResponse
    {
        $ten = $lich->TenKhoa;
        $lich->delete();

        return redirect()
            ->route('qldtoto.lich-du-kien.thuc-hanh.index')
            ->with('success', 'Đã xoá lịch dự kiến "'.$ten.'".');
    }

    /** Nút "Sinh lịch" gửi kèm hanh_dong=sinh_lich: lưu form rồi sinh lại toàn bộ buổi. */
    private function sauKhiLuu(Request $request, LichDuKienThucHanh $lich, string $thongBao): RedirectResponse
    {
        $redirect = redirect()->route('qldtoto.lich-du-kien.thuc-hanh.edit', $lich);
        if ($request->input('hanh_dong') !== 'sinh_lich') {
            return $redirect->with('success', $thongBao);
        }

        $kq = app(SinhLichDuKienThucHanh::class)->sinh($lich->fresh());
        $thongBao = 'Đã sinh lịch: '.$kq['so_buoi'].' buổi'
            .($kq['ngay_ket_thuc'] ? ', kết thúc '.Carbon::parse($kq['ngay_ket_thuc'])->format('d/m/Y') : '').'.'
            .collect(['cabin' => 'Cabin', 'tu_dong' => 'Tự động'])
                ->filter(fn (string $nhan, string $khoi): bool => ! empty($kq['khoi'][$khoi]))
                ->map(fn (string $nhan, string $khoi): string => ' '.$nhan.': '.collect($kq['khoi'][$khoi])
                    ->map(fn (array $ngay, int $cap): string => 'Cặp '.$cap.' '.Carbon::parse($ngay[0])->format('d/m')
                        .($ngay[1] !== $ngay[0] ? '–'.Carbon::parse($ngay[1])->format('d/m') : ''))
                    ->implode(', ').'.')
                ->implode('');

        return $redirect
            ->with('success', $thongBao)
            ->with('canh_bao_sinh_lich', $kq['canh_bao'])
            ->withFragment('lich-da-sinh');
    }

    /**
     * Bảng lịch đã sinh: mỗi dòng một ngày (kể cả ngày nghỉ), mỗi cặp 2 cột Sáng / Chiều.
     *
     * @return array{cot: list<array<string, mixed>>, dong: list<array<string, mixed>>, so_buoi: int}|null
     */
    private static function bangLichDaSinh(LichDuKienThucHanh $lich): ?array
    {
        $buois = $lich->buois()->orderBy('Ngay')->orderBy('CapThuTu')->orderBy('Ca', 'desc')->get();
        if ($buois->isEmpty()) {
            return null;
        }

        $tenGv = GiaoVien::query()
            ->whereIn('MaGV', $buois->pluck('MaGv')->filter()->unique()->values()->all())
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->mapWithKeys(fn (GiaoVien $g): array => [trim((string) $g->MaGV) => $g->HoTen])
            ->all();

        $caps = $buois->groupBy('CapThuTu')->sortKeys();
        $cot = [];
        foreach ($caps as $capThuTu => $dsBuoi) {
            foreach (array_keys(BaiDay::CA) as $ca) {
                $buoi = $dsBuoi->firstWhere('Ca', $ca);
                $cot[] = [
                    'key' => $capThuTu.'|'.$ca,
                    'cap' => (int) $capThuTu,
                    'ca' => $ca,
                    'xe' => (string) $dsBuoi->first()->BienSoXe,
                    'ma_gv' => (string) $buoi?->MaGv,
                    'ten_gv' => $buoi ? ($tenGv[$buoi->MaGv] ?? $buoi->MaGv) : '',
                ];
            }
        }

        $theoNgay = $buois->groupBy(fn ($b) => $b->Ngay->format('Y-m-d'));
        $nghi = $lich->ngayNghis->mapWithKeys(fn ($n) => [$n->Ngay->format('Y-m-d') => (string) $n->GhiChu])->all();
        $thu = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $dong = [];
        $ngay = Carbon::parse($buois->first()->Ngay)->startOfDay();
        $cuoi = Carbon::parse($buois->max('Ngay'))->startOfDay();
        for (; $ngay->lte($cuoi); $ngay->addDay()) {
            $ymd = $ngay->format('Y-m-d');
            $dong[] = [
                'ngay' => $ngay->format('d/m/Y'),
                'thu' => $thu[$ngay->dayOfWeek],
                'nghi' => array_key_exists($ymd, $nghi),
                'ly_do_nghi' => $nghi[$ymd] ?? '',
                'o' => ($theoNgay[$ymd] ?? collect())->keyBy(fn ($b) => $b->CapThuTu.'|'.$b->Ca)->all(),
            ];
        }

        return ['cot' => $cot, 'dong' => $dong, 'so_buoi' => $buois->count()];
    }

    private function form(LichDuKienThucHanh $lich): View
    {
        $ngayNghi = old('ngay_nghi') ?? ($lich->exists
            ? $lich->ngayNghis->sortBy('Ngay')->map(fn ($n): array => [
                'ngay' => $n->Ngay->format('Y-m-d'),
                'ghi_chu' => (string) $n->GhiChu,
            ])->all()
            : []);

        $capGv = old('cap_gv') ?? ($lich->exists
            ? $lich->capGvs->sortBy('ThuTu')->map(fn ($c): array => [
                'gv_sang' => (string) $c->MaGvSang,
                'gv_chieu' => (string) $c->MaGvChieu,
                'xe' => (string) $c->BienSoXe,
            ])->values()->all()
            : []);
        $capGv = array_values((array) $capGv);
        if ($capGv === []) {
            $capGv[] = ['gv_sang' => '', 'gv_chieu' => '', 'xe' => ''];
        }

        $xeTuDong = (string) old('xe_tu_dong', $lich->XeTuDong);
        $maXeDangChon = array_filter([$xeTuDong, ...array_column($capGv, 'xe')]);
        $maGvDangChon = array_filter([...array_column($capGv, 'gv_sang'), ...array_column($capGv, 'gv_chieu')]);
        $xeOptions = self::xeOptions($maXeDangChon);

        return view('QldtOto.lich-du-kien.thuc-hanh.form', [
            'lich' => $lich,
            'hangOptions' => BaiDay::HANG,
            'ngayNghi' => array_values((array) $ngayNghi),
            'capGv' => $capGv,
            'xeTuDong' => $xeTuDong,
            'xeOptions' => $xeOptions,
            'xeTuDongOptions' => array_filter(
                $xeOptions,
                fn (array $x): bool => $x['hang'] === self::HANG_XE_TU_DONG || $x['bien_so'] === $xeTuDong
            ),
            'gvOptions' => self::giaoVienOptions($maGvDangChon, array_column($xeOptions, 'bien_so')),
            'bangLich' => $lich->exists ? self::bangLichDaSinh($lich) : null,
        ]);
    }

    private function luu(Request $request, LichDuKienThucHanh $lich): void
    {
        $ngayNghi = array_values(array_filter(
            (array) $request->input('ngay_nghi', []),
            static fn ($r): bool => is_array($r) && trim((string) ($r['ngay'] ?? '')) !== ''
        ));
        $capGv = array_values(array_filter(
            (array) $request->input('cap_gv', []),
            static fn ($r): bool => is_array($r) && trim(implode('', array_map('strval', $r))) !== ''
        ));
        $request->merge(['ngay_nghi' => $ngayNghi, 'cap_gv' => $capGv]);

        $validator = validator($request->all(), [
            'ten_khoa' => ['required', 'string', 'max:255'],
            'hang' => ['required', Rule::in(array_keys(BaiDay::HANG))],
            'ngay_bat_dau' => ['required', 'date_format:Y-m-d'],
            'xe_tu_dong' => ['nullable', 'string', 'max:20', Rule::exists('sqlsrv.XeTap', 'BienSoXe')],
            'cabin_ngay' => ['nullable', 'date_format:Y-m-d'],
            'ngay_nghi' => ['array'],
            'ngay_nghi.*.ngay' => ['required', 'date_format:Y-m-d', 'distinct'],
            'ngay_nghi.*.ghi_chu' => ['nullable', 'string', 'max:255'],
            'cap_gv' => ['array'],
            'cap_gv.*.gv_sang' => ['nullable', 'string', 'max:20', Rule::exists('sqlsrv.GiaoVien', 'MaGV')],
            'cap_gv.*.gv_chieu' => ['nullable', 'string', 'max:20', Rule::exists('sqlsrv.GiaoVien', 'MaGV')],
            'cap_gv.*.xe' => ['nullable', 'string', 'max:20', Rule::exists('sqlsrv.XeTap', 'BienSoXe')],
        ], [
            'ten_khoa.required' => 'Nhập Tên khoá.',
            'hang.required' => 'Chọn Hạng.',
            'hang.in' => 'Hạng không hợp lệ.',
            'ngay_bat_dau.required' => 'Nhập Ngày bắt đầu thực hành.',
            'ngay_bat_dau.date_format' => 'Ngày bắt đầu thực hành không hợp lệ.',
            'cabin_ngay.date_format' => 'Ngày cabin của cặp trước không hợp lệ.',
            'ngay_nghi.*.ngay.date_format' => 'Ngày nghỉ dòng :position không hợp lệ.',
            'ngay_nghi.*.ngay.distinct' => 'Ngày nghỉ dòng :position bị trùng.',
            'ngay_nghi.*.ghi_chu.max' => 'Ghi chú ngày nghỉ dòng :position tối đa 255 ký tự.',
            'xe_tu_dong.exists' => 'Xe tự động không có trong danh mục xe.',
            'cap_gv.*.gv_sang.exists' => 'Cặp :position: giáo viên sáng không có trong danh mục.',
            'cap_gv.*.gv_chieu.exists' => 'Cặp :position: giáo viên chiều không có trong danh mục.',
            'cap_gv.*.xe.exists' => 'Cặp :position: xe không có trong danh mục.',
        ]);
        $validator->after(function ($v) use ($capGv): void {
            $gvDaDung = [];
            $xeDaDung = [];
            foreach ($capGv as $i => $c) {
                $sang = trim((string) ($c['gv_sang'] ?? ''));
                $chieu = trim((string) ($c['gv_chieu'] ?? ''));
                $xe = trim((string) ($c['xe'] ?? ''));
                $cap = 'Cặp '.($i + 1);
                if ($sang === '' && $chieu === '') {
                    $v->errors()->add("cap_gv.$i", $cap.': chọn ít nhất một giáo viên.');
                }
                foreach (array_filter([$sang, $chieu]) as $ma) {
                    $key = mb_strtoupper($ma);
                    if (isset($gvDaDung[$key])) {
                        $v->errors()->add("cap_gv.$i", $cap.': giáo viên '.$ma.' đã có ở '.$gvDaDung[$key].'.');
                    }
                    $gvDaDung[$key] = $cap;
                }
                if ($xe !== '') {
                    $key = mb_strtoupper($xe);
                    if (isset($xeDaDung[$key])) {
                        $v->errors()->add("cap_gv.$i", $cap.': xe '.$xe.' đã dùng ở '.$xeDaDung[$key].'.');
                    }
                    $xeDaDung[$key] = $cap;
                }
            }
        });
        $validator->validate();

        $now = Carbon::now();
        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($request, $lich, $ngayNghi, $capGv, $now): void {
            $lich->fill([
                'TenKhoa' => trim((string) $request->input('ten_khoa')),
                'Hang' => (string) $request->input('hang'),
                'NgayBatDauThucHanh' => $request->input('ngay_bat_dau'),
                'XeTuDong' => $request->input('xe_tu_dong') ?: null,
                'CabinCapTruocNgay' => $request->input('cabin_ngay') ?: null,
                'NgayCapNhat' => $now,
            ])->save();

            LichDuKienThucHanhNgayNghi::query()->where('LichDuKienId', $lich->Id)->delete();
            $rows = collect($ngayNghi)
                ->sortBy('ngay')
                ->map(fn (array $r): array => [
                    'LichDuKienId' => $lich->Id,
                    'Ngay' => $r['ngay'],
                    'GhiChu' => trim((string) ($r['ghi_chu'] ?? '')) ?: null,
                ])
                ->values()
                ->all();
            foreach (array_chunk($rows, 200) as $chunk) {
                LichDuKienThucHanhNgayNghi::query()->insert($chunk);
            }

            LichDuKienThucHanhCapGv::query()->where('LichDuKienId', $lich->Id)->delete();
            $don = static fn ($v): ?string => trim((string) $v) ?: null;
            foreach ($capGv as $i => $c) {
                LichDuKienThucHanhCapGv::query()->create([
                    'LichDuKienId' => $lich->Id,
                    'ThuTu' => $i + 1,
                    'MaGvSang' => $don($c['gv_sang'] ?? null),
                    'MaGvChieu' => $don($c['gv_chieu'] ?? null),
                    'BienSoXe' => $don($c['xe'] ?? null),
                ]);
            }
        });
    }

    /**
     * Xe đang hoạt động (+ xe đã chọn dù đã ngừng) — sắp theo hạng rồi biển số.
     *
     * @param  list<string>  $dangChon
     * @return list<array{bien_so: string, nhan: string, hang: string}>
     */
    private static function xeOptions(array $dangChon): array
    {
        return XeTap::query()
            ->where(fn ($q) => $q->where('TrangThai', 1)->orWhereIn('BienSoXe', $dangChon ?: ['']))
            ->orderBy('HangGPLXXe')
            ->orderBy('BienSoXe')
            ->get(['BienSoXe', 'NhanHieu', 'HangGPLXXe', 'TrangThai'])
            ->map(fn (XeTap $x): array => [
                'bien_so' => trim((string) $x->BienSoXe),
                'nhan' => trim($x->BienSoXe.' — '.trim((string) $x->NhanHieu).($x->TrangThai ? '' : ' (ngừng hoạt động)')),
                'hang' => trim((string) $x->HangGPLXXe),
            ])
            ->all();
    }

    /**
     * Giáo viên đang hoạt động (+ GV đã chọn). 'xe' = xe đã gán cho GV (ô Ghi chú bên danh mục GV) nếu là biển số hợp lệ.
     *
     * @param  list<string>  $dangChon
     * @param  list<string>  $bienSoHopLe
     * @return list<array{ma: string, nhan: string, xe: string}>
     */
    private static function giaoVienOptions(array $dangChon, array $bienSoHopLe): array
    {
        $hopLe = array_flip($bienSoHopLe);

        return GiaoVien::query()
            ->where(fn ($q) => $q->where('TrangThai', 1)->orWhereIn('MaGV', $dangChon ?: ['']))
            ->orderBy('TenGV')
            ->orderBy('HoTenDem')
            ->get(['MaGV', 'HoTenDem', 'TenGV', 'GhiChu', 'TrangThai'])
            ->map(function (GiaoVien $g) use ($hopLe): array {
                $xe = trim((string) $g->GhiChu);

                return [
                    'ma' => trim((string) $g->MaGV),
                    'nhan' => $g->HoTen.' ('.trim((string) $g->MaGV).')'.($g->TrangThai ? '' : ' — ngừng'),
                    'xe' => isset($hopLe[$xe]) ? $xe : '',
                ];
            })
            ->all();
    }
}
