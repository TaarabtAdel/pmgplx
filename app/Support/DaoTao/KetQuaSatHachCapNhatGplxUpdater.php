<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\CapNhatGPLXHocVien;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Ghi cột «Tổng hợp» (R) vào CapNhatGPLXHocVien.KetQua — chỉ học viên đã có trong danh sách. */
class KetQuaSatHachCapNhatGplxUpdater
{
    /**
     * @return array{rows: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function analyzeFromFile(string $path): array
    {
        $records = (new KetQuaSatHachExcelParser())->readAllRecordsFromFile($path);
        $khop = new KetQuaSatHachThuTienUpdater();
        $pmglxIndex = $khop->buildPmglxIndexForRecords($records);
        $gplxByMaDk = CapNhatGPLXHocVien::query()->get(['Id', 'MaDK', 'KetQua'])
            ->keyBy(fn ($r) => (string) $r->MaDK);

        $rows = [];
        $meta = [
            'record_count' => count($records),
            'matched' => 0,
            'not_in_gplx' => 0,
            'not_found' => 0,
            'ambiguous' => 0,
            'will_update' => 0,
            'skip' => 0,
            'mon_cap_nhat' => 0,
        ];

        foreach ($records as $record) {
            $row = $this->analyzeOne($record, $khop, $pmglxIndex, $gplxByMaDk);
            $rows[] = $row;
            if ($row['status'] === 'matched') {
                $meta['matched']++;
            } elseif ($row['status'] === 'not_in_gplx') {
                $meta['not_in_gplx']++;
            } elseif ($row['status'] === 'not_found') {
                $meta['not_found']++;
                $meta['skip']++;
            } elseif ($row['status'] === 'ambiguous') {
                $meta['ambiguous']++;
                $meta['skip']++;
            } else {
                $meta['skip']++;
            }
            if ($row['will_apply']) {
                $meta['will_update']++;
            }
        }

        return ['rows' => $rows, 'meta' => $meta];
    }

    /** @return array{updated_hv: int, updated_mon: int, skipped: int} */
    public function applyFromFile(string $path): array
    {
        $analysis = $this->analyzeFromFile($path);
        $updatedHv = 0;
        $skipped = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($analysis, &$updatedHv, &$skipped): void {
            foreach ($analysis['rows'] as $row) {
                if (! $row['will_apply'] || empty($row['gplx_id'])) {
                    $skipped++;

                    continue;
                }
                $hv = CapNhatGPLXHocVien::query()->lockForUpdate()->find($row['gplx_id']);
                if (! $hv) {
                    $skipped++;

                    continue;
                }
                $moi = (string) ($row['tong_hop'] ?? '');
                if ($moi === '' || $moi === (string) ($hv->KetQua ?? '')) {
                    $skipped++;

                    continue;
                }
                $hv->KetQua = $moi;
                $hv->NgayCapNhat = Carbon::now();
                $hv->save();
                $updatedHv++;
            }
        });

        return ['updated_hv' => $updatedHv, 'updated_mon' => 0, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  \Illuminate\Support\Collection<string, CapNhatGPLXHocVien>  $gplxByMaDk
     * @return array<string, mixed>
     */
    private function analyzeOne(array $record, KetQuaSatHachThuTienUpdater $khop, array $pmglxIndex, $gplxByMaDk): array
    {
        $cccd = (string) ($record['so_dinh_danh'] ?? '');
        $hangFile = (string) ($record['hang'] ?? '');
        $tongHop = trim((string) ($record['tong_hop'] ?? ''));
        $candidates = $khop->findPmglxCandidates($pmglxIndex, $cccd, $hangFile);

        $base = [
            'row' => $record['row'] ?? null,
            'stt' => $record['stt'] ?? '',
            'ho_ten' => $record['ho_ten'] ?? '',
            'so_dinh_danh' => $record['so_dinh_danh_raw'] ?? $cccd,
            'hang_file' => $hangFile,
            'hang_file_label' => HangSatHachMatch::labelForFileHang($hangFile),
            'tong_hop' => $tongHop,
            'mon' => [],
            'will_apply' => false,
            'gplx_id' => null,
        ];

        if ($candidates === []) {
            return array_merge($base, [
                'status' => 'not_found',
                'message' => 'Không tìm thấy trong PMGPLX (Số CMT + hạng).',
                'ma_dk' => null,
            ]);
        }
        if (count($candidates) > 1) {
            return array_merge($base, [
                'status' => 'ambiguous',
                'message' => 'Nhiều học viên PMGPLX trùng Số CMT + hạng: '.implode(', ', array_column($candidates, 'ma_dk')),
                'ma_dk' => null,
            ]);
        }

        $match = $candidates[0];
        $maDk = (string) $match['ma_dk'];
        /** @var CapNhatGPLXHocVien|null $gplx */
        $gplx = $gplxByMaDk->get($maDk);
        $shared = [
            'ma_dk' => $maDk,
            'hang_pmglx' => $match['hang_gplx'] ?? null,
            'hang_dao_tao' => $match['hang_dao_tao'] ?? null,
            'ket_qua_hien_tai' => $gplx ? (string) ($gplx->KetQua ?? '') : '',
        ];

        if (! $gplx) {
            return array_merge($base, $shared, [
                'status' => 'not_in_gplx',
                'message' => 'Khớp PMGPLX nhưng chưa có trong danh sách cập nhật GPLX — thêm học viên trước, không tạo mới khi import.',
            ]);
        }

        if ($tongHop === '') {
            return array_merge($base, $shared, [
                'status' => 'matched',
                'message' => 'Cột Tổng hợp (R) trống — không ghi.',
                'gplx_id' => $gplx->Id,
                'will_apply' => false,
            ]);
        }

        $giong = $tongHop === trim((string) ($gplx->KetQua ?? ''));

        return array_merge($base, $shared, [
            'status' => 'matched',
            'message' => $giong ? 'Kết quả đã khớp' : 'Ghi «'.$tongHop.'»',
            'gplx_id' => $gplx->Id,
            'will_apply' => ! $giong,
        ]);
    }
}
