<?php

namespace App\Http\Controllers\QldtOto;

final class BienBanHopKtmController extends BaoCaoKtmTuChiTietController
{
    public const SLUG = 'bien-ban-hop';

    protected function loaiXuat(): string
    {
        return 'bb_hop';
    }

    protected function metaTrang(): array
    {
        return [
            'title' => 'Biên bản họp thông qua KTM',
            'sheetLabel' => 'Biên bản họp',
            'filePrefix' => 'BB-HOP',
        ];
    }
}