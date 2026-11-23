<?php

namespace App\Http\Controllers\QldtOto;

final class BaoCaoChungKtmController extends BaoCaoKtmTuChiTietController
{
    public const SLUG = 'bao-cao-chung';

    protected function loaiXuat(): string
    {
        return 'bc_chung';
    }

    protected function metaTrang(): array
    {
        return [
            'title' => 'Báo cáo chung',
            'sheetLabel' => 'BC CHUNG',
            'filePrefix' => 'BC-CHUNG',
        ];
    }
}