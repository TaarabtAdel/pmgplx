<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Support\PMGPLX\LichExcelBienSo;
use Carbon\Carbon;

class DatTuyenDuongTuXeOnline
{
    public function __construct(
        private readonly DatXeOnlineClient $client = new DatXeOnlineClient(),
        private readonly DatTuyenDuongGpsMatcher $matcher = new DatTuyenDuongGpsMatcher(),
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     message?: string,
     *     tuyen_duong?: string,
     *     du_lieu?: array<string, mixed>
     * }
     */
    public function forPhien(DatDSPhien $phien): array
    {
        if (! DatXeOnlineBearerToken::has()) {
            return [
                'ok' => false,
                'message' => 'Chưa cấu hình Bearer token (bấm Cấu hình Bearer).',
            ];
        }

        $start = $phien->ThoiGianBatDauPhienHoc;
        $end = $phien->ThoiGianKetThucPhienHoc;
        if (! $start instanceof Carbon || ! $end instanceof Carbon) {
            return [
                'ok' => false,
                'message' => 'Phiên thiếu thời gian bắt đầu / kết thúc.',
            ];
        }

        $bienSo = LichExcelBienSo::normalize((string) ($phien->BienSoXe ?? ''));
        if ($bienSo === '') {
            return [
                'ok' => false,
                'message' => 'Phiên thiếu biển số xe.',
            ];
        }

        try {
            $segments = $this->client->fetchSegments(
                $start,
                $end,
                $bienSo,
                DatXeOnlineBearerToken::get()
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        if ($segments === []) {
            return [
                'ok' => false,
                'message' => 'Không có dữ liệu GPS từ XeOnline.',
            ];
        }

        $analysis = $this->matcher->analyze($segments);
        $tuyenDuong = $analysis['ten_tuyen_mac_dinh'];

        return [
            'ok' => true,
            'tuyen_duong' => $tuyenDuong !== '' ? $tuyenDuong : '—',
            'du_lieu' => [
                'phien_id' => (int) $phien->Id,
                'ma_phien' => (string) ($phien->MaPhienHoc ?? ''),
                'bien_so_xe' => $bienSo,
                'thoi_gian_bat_dau' => $start->format('Y-m-d H:i:s'),
                'thoi_gian_ket_thuc' => $end->format('Y-m-d H:i:s'),
                'ten_tuyen_mac_dinh' => $analysis['ten_tuyen_mac_dinh'],
                'tong_km' => $analysis['tong_km'],
                'so_diem_gps' => $analysis['so_diem_gps'],
                'so_segment' => $analysis['so_segment'],
                'ten_tuyen_trong_api' => $analysis['ten_tuyen_trong_api'],
                'ten_tuyen_nominatim' => $analysis['ten_tuyen_nominatim'],
                'ten_diem_dau' => $analysis['ten_diem_dau'],
                'ten_diem_cuoi' => $analysis['ten_diem_cuoi'],
                'tom_tat_gps' => $analysis['tom_tat_gps'],
                'diem_dau' => $analysis['diem_dau'],
                'diem_cuoi' => $analysis['diem_cuoi'],
                'nominatim' => $analysis['nominatim'],
                'list_coordinate' => $analysis['list_coordinate'],
                'segments' => $analysis['segments'],
            ],
        ];
    }
}
