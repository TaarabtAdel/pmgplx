<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\TienDoDaoTao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BaoCaoLuuLuongDaoTaoController extends Controller
{
    /** Hạn mức từ thành lập đến hết 26/07/2026 */
    private const HAN_MUC_GIAI_DOAN_1 = 499;

    /** Hạn mức từ 27/07/2026 trở đi */
    private const HAN_MUC_GIAI_DOAN_2 = 1000;

    private const MOC_HAN_MUC = '2026-07-27';

    public function index(Request $request): View
    {
        $filters = [
            'ngay_kiem_tra' => (string) ($request->input('ngay_kiem_tra') ?? now()->format('Y-m-d')),
            'ky_hieu' => trim((string) ($request->input('ky_hieu') ?? '')),
            'hang_gplx' => trim((string) ($request->input('hang_gplx') ?? '')),
        ];

        $searched = ! $request->boolean('lam_moi');

        $summary = null;
        /** @var list<array{hang: string, so_luong: int}> */
        $items = [];
        /** @var list<array<string, mixed>> */
        $classes = [];

        if ($searched) {
            $at = Carbon::parse($filters['ngay_kiem_tra'])->startOfDay();
            $report = TienDoDaoTao::baoCaoLuuLuongAt($at, $filters['hang_gplx'], $filters['ky_hieu']);
            $items = $report['items'];
            $classes = $report['classes'];
            $tongSo = $report['tong_so'];
            $hanMuc = $this->hanMuc($at);

            $summary = [
                'tong_so' => $tongSo,
                'han_muc' => $hanMuc,
                'trong_han_muc' => $tongSo <= $hanMuc,
                'ngay_hien_thi' => $at->format('d/m/Y'),
                'ky_hieu_loc' => $filters['ky_hieu'],
                'so_lop' => count($classes),
                'han_muc_mo_ta' => $this->hanMucMoTa($at),
            ];
        }

        return view('DaoTao.bao-cao.luu-luong-dao-tao', [
            'filters' => $filters,
            'hangOptions' => TienDoDaoTao::hangOptions(),
            'kyHieuOptions' => TienDoDaoTao::kyHieuFilterOptions(),
            'searched' => $searched,
            'summary' => $summary,
            'items' => $items,
            'classes' => $classes,
        ]);
    }

    private function hanMuc(Carbon $at): int
    {
        $moc = Carbon::parse(self::MOC_HAN_MUC)->startOfDay();

        return $at->lt($moc) ? self::HAN_MUC_GIAI_DOAN_1 : self::HAN_MUC_GIAI_DOAN_2;
    }

    private function hanMucMoTa(Carbon $at): string
    {
        if ($at->lt(Carbon::parse(self::MOC_HAN_MUC)->startOfDay())) {
            return 'Hạn mức giai đoạn 1: '.number_format(self::HAN_MUC_GIAI_DOAN_1).' (đến 26/07/2026)';
        }

        return 'Hạn mức giai đoạn 2: '.number_format(self::HAN_MUC_GIAI_DOAN_2).' (từ 27/07/2026)';
    }
}
