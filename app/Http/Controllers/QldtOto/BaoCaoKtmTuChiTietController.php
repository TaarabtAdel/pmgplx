<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\MenuQldt;
use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoFormMacDinh;
use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoThamSo;
use App\Support\QldtOto\KtmChiTiet\KtmChiTietGopDuLieu;
use App\Support\QldtOto\KtmChiTiet\XuatKtmChuanExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class BaoCaoKtmTuChiTietController extends Controller
{
    abstract protected function loaiXuat(): string;

    /** @return array{title: string, sheetLabel: string, filePrefix: string} */
    abstract protected function metaTrang(): array;

    public function index(Request $request): View
    {
        $trang = MenuQldt::timTrang($this::SLUG);
        abort_if($trang === null, 404);

        $danhSach = \App\Models\QldtOto\KtmBaoCaoChiTiet::query()
            ->with('baoCaoGoc')
            ->orderByDesc('NgayKyThi')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $cfg = config('qldt_oto_ktm_bao_cao', []);
        $meta = $this->metaTrang();

        return view('QldtOto.bao-cao-ktm-tu-chi-tiet.index', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
            'danhSach' => $danhSach,
            'cfg' => $cfg,
            'meta' => $meta,
            'loaiXuat' => $this->loaiXuat(),
            'exportRoute' => 'qldtoto.'.$this::SLUG.'.export',
            'idsChon' => array_map('intval', (array) $request->query('id', [])),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer'],
            'ngay_lap' => ['nullable', 'date'],
            'ngay_thi' => ['nullable', 'string', 'max:32'],
            'sat_hach' => ['nullable', 'string', 'max:255'],
            'ngay_hop' => ['nullable', 'string', 'max:32'],
            'gio_hop' => ['nullable', 'string', 'max:32'],
            'dia_diem_hop' => ['nullable', 'string', 'max:255'],
            'so_qd' => ['nullable', 'string', 'max:64'],
            'ngay_qd' => ['nullable', 'string', 'max:32'],
            'ten_khoa' => ['nullable', 'string', 'max:500'],
            'tieu_de_hop' => ['nullable', 'string', 'max:500'],
            'doan_mo_dau' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $dl = KtmChiTietGopDuLieu::tuBanGhiIds($data['record_ids']);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $form = KtmBaoCaoFormMacDinh::tao($dl);
        $ngayLap = KtmBaoCaoFormMacDinh::ngayTuInput($data['ngay_lap'] ?? '') ?: $dl->ngayThi;
        $form['bbth']['ngay_lap'] = $ngayLap;
        $form['bbth']['ngay_hop'] = $data['ngay_hop'] ?? $dl->ngayThi;
        $form['bbth']['gio_hop'] = $data['gio_hop'] ?? ($form['bbth']['gio_hop'] ?? '');
        $form['bbth']['dia_diem_hop'] = $data['dia_diem_hop'] ?? ($form['bbth']['dia_diem_hop'] ?? '');
        $form['bbth']['so_qd'] = $data['so_qd'] ?? '';
        $form['bbth']['ngay_qd'] = $data['ngay_qd'] ?? '';
        $form['bbth']['ten_khoa'] = $data['ten_khoa'] ?? $dl->tenKhoa;
        $form['bbth']['tieu_de_hop'] = $data['tieu_de_hop'] ?? '';
        $doan = trim((string) ($data['doan_mo_dau'] ?? ''));
        if ($doan !== '') {
            if ($this->loaiXuat() === 'bb_hop') {
                $form['bbth']['doan_mo_dau_hop'] = $doan;
            } else {
                $form['bbth']['doan_mo_dau_bbth'] = $doan;
            }
        }
        $form['bc_chung']['ngay_lap'] = $ngayLap;
        $form['bc_chung']['ngay_thi'] = $data['ngay_thi'] ?? $dl->ngayThi;
        $form['bc_chung']['sat_hach'] = $data['sat_hach'] ?? ($form['bc_chung']['sat_hach'] ?? '');

        $ts = KtmBaoCaoThamSo::tuForm($form, $dl);

        $meta = $this->metaTrang();
        $tmp = storage_path('app/tmp/'.$meta['filePrefix'].'-'.uniqid('', true).'.xls');
        try {
            match ($this->loaiXuat()) {
                'bc_chung' => XuatKtmChuanExcel::xuatBcChung($dl, $ts, $tmp),
                'bbth' => XuatKtmChuanExcel::xuatBbth($dl, $ts, $tmp),
                'bb_hop' => XuatKtmChuanExcel::xuatBbHop($dl, $ts, $tmp),
                default => throw new \InvalidArgumentException('Loại xuất không hợp lệ.'),
            };
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Xuất Excel thất bại: '.$e->getMessage());
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $dl->tenKhoa) ?: 'ktm';

        return response()
            ->download($tmp, $meta['filePrefix'].'-'.$safe.'.xls')
            ->deleteFileAfterSend(true);
    }
}