<?php

namespace App\Http\Controllers\QldtOto;

final class BaoCaoTongHopKtmController extends BaoCaoKtmTuChiTietController
{
    public const SLUG = 'bao-cao-tong-hop';

    protected function loaiXuat(): string
    {
        return 'bbth';
    }

    protected function metaTrang(): array
    {
        return [
            'title' => 'Báo cáo tổng hợp (BBTH)',
            'sheetLabel' => 'BBTH',
            'filePrefix' => 'BBTH',
        ];
    }
}