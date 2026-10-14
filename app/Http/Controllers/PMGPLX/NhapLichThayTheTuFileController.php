<?php

namespace App\Http\Controllers\PMGPLX;

use Illuminate\View\View;

/** Nhập lịch thay thế: cập nhật GV/xe theo khung giờ trùng trên cùng khóa, không xóa cả khóa. */
class NhapLichThayTheTuFileController extends NhapLichTuFileController
{
    protected function sessionExcelKey(): string
    {
        return 'pmgplx.lich.nhap_thay_the.excel';
    }

    protected function sessionSaveKey(): string
    {
        return 'pmgplx.lich.nhap_thay_the.save';
    }

    protected function nhapRoutePrefix(): string
    {
        return 'pmgplx.lich.nhap-thay-the.';
    }

    protected function isSlotReplaceImport(): bool
    {
        return true;
    }

    public function create(): View
    {
        return view('PMGPLX.lich.nhap-thay-the', $this->enrichImportViewData([]));
    }
}
