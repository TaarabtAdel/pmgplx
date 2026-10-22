<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Models\QldtOto\BaiDay;
use App\Models\QldtOto\BaiHoc;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CauHinhBaiDayController extends Controller
{
    public function index(?string $hang = null, ?string $ca = null): View|RedirectResponse
    {
        if ($hang === null || $ca === null) {
            return redirect()->route('qldtoto.cau-hinh-bai-day.index', [
                'hang' => $hang ?? array_key_first(BaiDay::HANG),
                'ca' => array_key_first(BaiDay::CA),
            ]);
        }

        $rows = BaiDay::query()
            ->where('Hang', $hang)
            ->where('Ca', $ca)
            ->orderBy('ThuTu')
            ->orderBy('Id')
            ->get();

        $soBai = [];
        foreach (BaiDay::query()->selectRaw('Hang, Ca, COUNT(*) as cnt')->groupBy('Hang', 'Ca')->get() as $r) {
            $soBai[$r->Hang.'|'.$r->Ca] = (int) $r->cnt;
        }

        return view('QldtOto.cau-hinh-bai-day', [
            'hang' => $hang,
            'ca' => $ca,
            'nhanTab' => self::nhanTab($hang, $ca),
            'hangOptions' => BaiDay::HANG,
            'caOptions' => BaiDay::CA,
            'soBai' => $soBai,
            'baiHocs' => self::danhSachBaiHoc($hang, $ca),
            'rows' => old('bai')
                ?? $rows->map(fn (BaiDay $r): array => [
                    'ngay_so' => $r->NgaySo,
                    'so_gio' => $r->SoGio !== null ? rtrim(rtrim(number_format($r->SoGio, 2, '.', ''), '0'), '.') : '',
                    'bai_hoc_id' => $r->BaiHocId,
                ])->all(),
        ]);
    }

    public function update(Request $request, string $hang, string $ca): RedirectResponse
    {
        $rows = array_values(array_filter(
            (array) $request->input('bai', []),
            static fn ($r): bool => is_array($r) && trim(implode('', array_map('strval', $r))) !== ''
        ));
        $request->merge(['bai' => $rows]);

        $request->validate([
            'bai' => ['array'],
            'bai.*.ngay_so' => ['required', 'integer', 'min:1', 'max:999'],
            'bai.*.so_gio' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'bai.*.bai_hoc_id' => [
                'required',
                'integer',
                Rule::exists('sqlsrv_manhlinh.QldtOtoBaiHoc', 'Id')->where('Hang', $hang)->where('Ca', $ca),
            ],
        ], [
            'bai.*.ngay_so.required' => 'Dòng :position: nhập Ngày số.',
            'bai.*.ngay_so.integer' => 'Dòng :position: Ngày số phải là số nguyên.',
            'bai.*.ngay_so.min' => 'Dòng :position: Ngày số từ 1.',
            'bai.*.so_gio.numeric' => 'Dòng :position: Số giờ phải là số.',
            'bai.*.so_gio.max' => 'Dòng :position: Số giờ tối đa 24.',
            'bai.*.bai_hoc_id.required' => 'Dòng :position: chưa có Nội dung — kéo bài học vào ô Nội dung.',
            'bai.*.bai_hoc_id.integer' => 'Dòng :position: chưa có Nội dung — kéo bài học vào ô Nội dung.',
            'bai.*.bai_hoc_id.exists' => 'Dòng :position: bài học không thuộc '.self::nhanTab($hang, $ca).' (có thể vừa bị xoá).',
        ]);

        $baiHocs = BaiHoc::query()->where('Hang', $hang)->where('Ca', $ca)->get()->keyBy('Id');
        $now = Carbon::now();
        $payload = [];
        foreach ($rows as $i => $r) {
            $baiHoc = $baiHocs[(int) $r['bai_hoc_id']];
            $soGio = trim((string) ($r['so_gio'] ?? ''));
            $phutBd = BaiDay::phutMotMoc($baiHoc->GioBatDau);
            $phutKt = BaiDay::phutMotMoc($baiHoc->GioKetThuc);
            if ($soGio === '' && $phutBd !== null && $phutKt !== null && $phutKt > $phutBd) {
                $soGio = (string) round(($phutKt - $phutBd) / 60, 2);
            }

            $payload[] = [
                'Hang' => $hang,
                'Ca' => $ca,
                'NgaySo' => (int) $r['ngay_so'],
                'SoGio' => $soGio !== '' ? (float) $soGio : null,
                'BaiHocId' => $baiHoc->Id,
                'ThuTu' => $i + 1,
                'NgayTao' => $now,
                'NgayCapNhat' => $now,
            ];
        }
        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($hang, $ca, $payload): void {
            BaiDay::query()->where('Hang', $hang)->where('Ca', $ca)->delete();
            foreach (array_chunk($payload, 100) as $chunk) {
                BaiDay::query()->insert($chunk);
            }
        });

        return redirect()
            ->route('qldtoto.cau-hinh-bai-day.index', ['hang' => $hang, 'ca' => $ca])
            ->with('success', 'Đã lưu '.count($payload).' bài dạy '.self::nhanTab($hang, $ca).'.');
    }

    public function themBaiHoc(Request $request, string $hang, string $ca): JsonResponse
    {
        $data = $this->kiemTraBaiHoc($request, $hang, $ca, null);
        $now = Carbon::now();
        $baiHoc = BaiHoc::query()->create($data + [
            'Hang' => $hang,
            'Ca' => $ca,
            'ThuTu' => (int) BaiHoc::query()->where('Hang', $hang)->where('Ca', $ca)->max('ThuTu') + 1,
            'NgayTao' => $now,
            'NgayCapNhat' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm bài học "'.$baiHoc->Ten.'".',
            'bai_hoc' => self::baiHocJson($baiHoc, 0),
        ]);
    }

    public function suaBaiHoc(Request $request, string $hang, string $ca, BaiHoc $baiHoc): JsonResponse
    {
        abort_unless($baiHoc->Hang === $hang && $baiHoc->Ca === $ca, 404);

        $baiHoc->fill($this->kiemTraBaiHoc($request, $hang, $ca, $baiHoc) + ['NgayCapNhat' => Carbon::now()])->save();
        $soNgay = $baiHoc->baiDays()->count();

        return response()->json([
            'message' => 'Đã cập nhật bài học "'.$baiHoc->Ten.'"'.($soNgay > 0 ? ' — áp dụng cho '.$soNgay.' ngày đang gán.' : '.'),
            'bai_hoc' => self::baiHocJson($baiHoc, $soNgay),
        ]);
    }

    public function xoaBaiHoc(string $hang, string $ca, BaiHoc $baiHoc): JsonResponse
    {
        abort_unless($baiHoc->Hang === $hang && $baiHoc->Ca === $ca, 404);

        $soNgay = $baiHoc->baiDays()->count();
        if ($soNgay > 0) {
            return response()->json([
                'message' => 'Không xoá được "'.$baiHoc->Ten.'" vì đang gán cho '.$soNgay.' ngày đã lưu. Đổi các ngày đó sang bài khác và lưu trước.',
            ], 422);
        }

        $baiHoc->delete();

        return response()->json(['message' => 'Đã xoá bài học "'.$baiHoc->Ten.'".']);
    }

    /** @return array{Ten: string, GioBatDau: ?string, GioKetThuc: ?string} */
    private function kiemTraBaiHoc(Request $request, string $hang, string $ca, ?BaiHoc $baiHoc): array
    {
        $request->merge([
            'ten' => BaiHoc::chuanHoaTen($request->input('ten')),
            'gio_bd' => trim((string) $request->input('gio_bd')),
            'gio_kt' => trim((string) $request->input('gio_kt')),
        ]);
        $request->validate([
            'ten' => [
                'required',
                'string',
                'max:500',
                Rule::unique('sqlsrv_manhlinh.QldtOtoBaiHoc', 'Ten')->where('Hang', $hang)->where('Ca', $ca)->ignore($baiHoc?->Id, 'Id'),
            ],
            'gio_bd' => ['nullable', 'string', 'max:50'],
            'gio_kt' => ['nullable', 'string', 'max:50'],
        ], [
            'ten.required' => 'Nhập tên bài học.',
            'ten.max' => 'Tên bài học tối đa 500 ký tự.',
            'ten.unique' => self::nhanTab($hang, $ca).' đã có bài học tên này.',
            'gio_bd.max' => 'Giờ BĐ tối đa 50 ký tự.',
            'gio_kt.max' => 'Giờ KT tối đa 50 ký tự.',
        ]);

        return [
            'Ten' => (string) $request->input('ten'),
            'GioBatDau' => $request->input('gio_bd') ?: null,
            'GioKetThuc' => $request->input('gio_kt') ?: null,
        ];
    }

    /** @return list<array{id: int, ten: string, gio_bd: string, gio_kt: string, so_ngay: int}> */
    private static function danhSachBaiHoc(string $hang, string $ca): array
    {
        $soNgay = BaiDay::query()
            ->where('Hang', $hang)
            ->where('Ca', $ca)
            ->selectRaw('BaiHocId, COUNT(*) as cnt')
            ->groupBy('BaiHocId')
            ->pluck('cnt', 'BaiHocId');

        return BaiHoc::query()
            ->where('Hang', $hang)
            ->where('Ca', $ca)
            ->orderBy('ThuTu')
            ->orderBy('Id')
            ->get()
            ->map(fn (BaiHoc $b): array => self::baiHocJson($b, (int) ($soNgay[$b->Id] ?? 0)))
            ->all();
    }

    /** @return array{id: int, ten: string, gio_bd: string, gio_kt: string, so_ngay: int} */
    private static function baiHocJson(BaiHoc $b, int $soNgay): array
    {
        return [
            'id' => (int) $b->Id,
            'ten' => (string) $b->Ten,
            'gio_bd' => (string) $b->GioBatDau,
            'gio_kt' => (string) $b->GioKetThuc,
            'so_ngay' => $soNgay,
        ];
    }

    private static function nhanTab(string $hang, string $ca): string
    {
        return 'Hạng '.BaiDay::HANG[$hang].' ('.BaiDay::CA[$ca].')';
    }
}
