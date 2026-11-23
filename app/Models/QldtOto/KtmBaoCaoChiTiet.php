<?php

namespace App\Models\QldtOto;

use App\Support\QldtOto\KtmChiTiet\DocDanhSachSatHach;
use Illuminate\Database\Eloquent\Model;

class KtmBaoCaoChiTiet extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'KtmBaoCaoChiTiet';

    protected $fillable = [
        'NgayKyThi',
        'TenKhoaThi',
        'TenKhoaTuFile',
        'DiaDiemSatHach',
        'LaThiLai',
        'BaoCaoGocId',
        'DuLieu',
        'CanhBao',
        'LichSuUpload',
    ];

    protected $casts = [
        'NgayKyThi' => 'date',
        'LaThiLai' => 'boolean',
        'BaoCaoGocId' => 'integer',
    ];

    public function baoCaoGoc(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'BaoCaoGocId');
    }

    /** @return list<array<string, mixed>> */
    public function thiSinh(): array
    {
        $raw = json_decode((string) $this->DuLieu, true);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $ts) {
            if (! is_array($ts)) {
                continue;
            }
            $sbd = (string) ($ts['sbd'] ?? '');
            $hoTen = (string) ($ts['hoTen'] ?? $ts['ho_ten'] ?? '');
            if (! DocDanhSachSatHach::dongThiSinhHopLe($sbd, $hoTen)) {
                continue;
            }
            $out[] = $ts;
        }

        return $out;
    }

    /** @return list<array{loai: string, ten_file: string, thoi_gian: string, so_hv: int}> */
    public function lichSuUploadList(): array
    {
        $raw = json_decode((string) ($this->LichSuUpload ?? '[]'), true);
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'loai' => (string) ($row['loai'] ?? ''),
                'ten_file' => (string) ($row['ten_file'] ?? ''),
                'thoi_gian' => (string) ($row['thoi_gian'] ?? ''),
                'so_hv' => (int) ($row['so_hv'] ?? 0),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public function canhBaoList(): array
    {
        $raw = json_decode((string) ($this->CanhBao ?? '[]'), true);

        return is_array($raw) ? array_values(array_filter($raw, 'is_string')) : [];
    }

    /**
     * Khôi phục cấu trúc danh sách từ JSON đã lưu (để ghép lại khi không tải file DS).
     *
     * @return array{
     *     ten_khoa: string,
     *     ngay_thi_tu_file: string,
     *     dia_diem: string,
     *     thi_sinh: list<array{stt: int, sbd: string, ho_ten: string, cccd: string, ngay_sinh: string, hang: string, ghi_chu: string}>
     * }
     */
    /**
     * @param  array<string, array<string, mixed>>  $ketQuaTheoCccd
     * @return array{lt: bool, mp: bool, cc: bool, h: bool, duong: bool}
     */
    public static function coMonDaNhap(array $ketQuaTheoCccd): array
    {
        $out = ['lt' => false, 'mp' => false, 'cc' => false, 'h' => false, 'duong' => false];
        foreach ($ketQuaTheoCccd as $key => $ts) {
            if (! is_array($ts) || str_starts_with((string) $key, 'sbd:')) {
                continue;
            }
            foreach (array_keys($out) as $field) {
                $v = trim((string) ($ts[$field] ?? ''));
                if ($v !== '' && $v !== '-') {
                    $out[$field] = true;
                }
            }
        }

        return $out;
    }

    /** @return array<string, array<string, mixed>> */
    public function ketQuaTheoCccd(): array
    {
        $map = [];
        foreach ($this->thiSinh() as $ts) {
            $cccd = preg_replace('/\D/', '', (string) ($ts['soDinhDanh'] ?? '')) ?? '';
            if ($cccd !== '') {
                $map[$cccd] = $ts;
            }
            $sbd = trim((string) ($ts['sbd'] ?? ''));
            if ($sbd !== '') {
                $map['sbd:'.$sbd] = $ts;
            }
        }

        return $map;
    }

    public function danhSachGoc(): array
    {
        $rows = [];
        $stt = 0;
        foreach ($this->thiSinh() as $ts) {
            $stt++;
            $rows[] = [
                'stt' => $stt,
                'sbd' => (string) ($ts['sbd'] ?? ''),
                'ho_ten' => (string) ($ts['hoTen'] ?? ''),
                'cccd' => (string) ($ts['soDinhDanh'] ?? ''),
                'ngay_sinh' => (string) ($ts['ngaySinh'] ?? ''),
                'hang' => (string) ($ts['hang'] ?? ''),
                'ghi_chu' => (string) ($ts['ghiChu'] ?? ''),
            ];
        }

        return [
            'ten_khoa' => (string) ($this->TenKhoaTuFile ?? ''),
            'ngay_thi_tu_file' => $this->NgayKyThi->format('d/m/Y'),
            'dia_diem' => (string) ($this->DiaDiemSatHach ?? ''),
            'thi_sinh' => $rows,
        ];
    }
}