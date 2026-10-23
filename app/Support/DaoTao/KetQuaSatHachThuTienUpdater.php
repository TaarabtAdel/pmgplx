<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\ThuTienHocVien;
use App\Models\PMGPLX\NguoiLX;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KetQuaSatHachThuTienUpdater
{
    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    public function analyzeFromFile(string $path): array
    {
        $records = (new KetQuaSatHachExcelParser())->readAllRecordsFromFile($path);

        return $this->analyzeRecords($records);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    public function analyzeRecords(array $records): array
    {
        $pmglxIndex = $this->buildPmglxIndexForRecords($records);
        $thuTienByMaDk = ThuTienHocVien::query()->get(['Id', 'MaDK', 'MaKhoaHoc'])
            ->keyBy(fn ($r) => (string) $r->MaDK);

        $rows = [];
        $meta = [
            'record_count' => count($records),
            'pmglx_matched' => 0,
            'matched' => 0,
            'will_add_thu_tien' => 0,
            'will_update' => 0,
            'skip' => 0,
            'mon_cap_nhat' => 0,
        ];

        foreach ($records as $record) {
            $row = $this->analyzeOne($record, $pmglxIndex, $thuTienByMaDk);
            $rows[] = $row;
            if (in_array($row['status'], ['matched', 'will_add_thu_tien'], true)) {
                $meta['pmglx_matched']++;
            }
            if ($row['status'] === 'matched') {
                $meta['matched']++;
            }
            if ($row['status'] === 'will_add_thu_tien') {
                $meta['will_add_thu_tien']++;
            }
            if (! in_array($row['status'], ['matched', 'will_add_thu_tien'], true)) {
                $meta['skip']++;
            }
            if ($row['will_apply']) {
                $meta['will_update']++;
                $meta['mon_cap_nhat'] += count(array_filter($row['mon'], fn (array $m): bool => $m['action'] === 'cap_nhat'));
            }
        }

        return ['rows' => $rows, 'meta' => $meta];
    }

    /**
     * @return array{updated_hv: int, updated_mon: int, skipped: int}
     */
    public function applyFromFile(string $path, ?int $nguoiId): array
    {
        $analysis = $this->analyzeFromFile($path);
        $updatedHv = 0;
        $updatedMon = 0;
        $skipped = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($analysis, $nguoiId, &$updatedHv, &$updatedMon, &$skipped): void {
            foreach ($analysis['rows'] as $row) {
                if (! $row['will_apply'] || empty($row['ma_dk'])) {
                    $skipped++;

                    continue;
                }
                $tt = $this->resolveThuTienForApply($row, $nguoiId);
                if (! $tt) {
                    $skipped++;

                    continue;
                }
                $tt = ThuTienHocVien::query()->lockForUpdate()->find($tt->Id);
                if (! $tt) {
                    $skipped++;

                    continue;
                }
                $changed = 0;
                foreach ($row['mon'] as $khoan => $mon) {
                    if ($mon['action'] !== 'cap_nhat' || $mon['kq'] === null) {
                        continue;
                    }
                    $n = $this->applyMon($tt, (string) $khoan, (string) $mon['kq'], (int) $mon['lan'], $nguoiId);
                    if ($n > 0) {
                        $changed += $n;
                    }
                    $tt->refresh();
                }
                if ($changed > 0) {
                    $updatedHv++;
                    $updatedMon += $changed;
                } else {
                    $skipped++;
                }
            }
        });

        return ['updated_hv' => $updatedHv, 'updated_mon' => $updatedMon, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $pmglxIndex
     * @param  \Illuminate\Support\Collection<string, ThuTienHocVien>  $thuTienByMaDk
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function analyzeOne(array $record, array $pmglxIndex, $thuTienByMaDk): array
    {
        $cccd = (string) ($record['so_dinh_danh'] ?? '');
        $hangFile = (string) ($record['hang'] ?? '');
        $candidates = $this->findPmglxCandidates($pmglxIndex, $cccd, $hangFile);

        $base = [
            'row' => $record['row'] ?? null,
            'stt' => $record['stt'] ?? '',
            'ho_ten' => $record['ho_ten'] ?? '',
            'so_dinh_danh' => $record['so_dinh_danh_raw'] ?? $cccd,
            'hang_file' => $hangFile,
            'hang_file_label' => HangSatHachMatch::labelForFileHang($hangFile),
            'tong_hop' => $record['tong_hop'] ?? '',
            'mon' => [],
            'will_apply' => false,
        ];

        if ($candidates === []) {
            return array_merge($base, [
                'status' => 'not_found',
                'message' => 'Không tìm thấy trong PMGPLX (Số CMT + hạng: file «'.$hangFile.'» ↔ HangGPLX / Hang ĐT).',
                'ma_dk' => null,
                'ma_khoa_hoc' => null,
                'thu_tien_id' => null,
            ]);
        }
        if (count($candidates) > 1) {
            $maList = implode(', ', array_column($candidates, 'ma_dk'));

            return array_merge($base, [
                'status' => 'ambiguous',
                'message' => 'Nhiều học viên PMGPLX trùng Số CMT + hạng: '.$maList,
                'ma_dk' => null,
                'ma_khoa_hoc' => null,
                'thu_tien_id' => null,
            ]);
        }

        $match = $candidates[0];
        $maDk = (string) $match['ma_dk'];
        /** @var ThuTienHocVien|null $tt */
        $tt = $thuTienByMaDk->get($maDk);
        if ($tt) {
            $tt = ThuTienHocVien::query()->with('lanThis')->find($tt->Id);
        }

        $monRows = [];
        $willApply = false;
        foreach (KetQuaSatHachExcelParser::MON as $khoan => $cols) {
            /** @var array{kq: ?string, kq_raw: string, lan: int} $src */
            $src = $record['mon'][$khoan] ?? ['kq' => null, 'kq_raw' => '', 'lan' => 1];
            if ($tt) {
                $monRows[$khoan] = $this->planMon($tt, $khoan, $src['kq'], (int) $src['lan'], $src['kq_raw']);
            } else {
                $monRows[$khoan] = $this->planMonMoi($khoan, $src['kq'], (int) $src['lan'], $src['kq_raw']);
            }
            if ($monRows[$khoan]['action'] === 'cap_nhat') {
                $willApply = true;
            }
        }

        $shared = [
            'ma_dk' => $maDk,
            'ma_khoa_hoc' => $match['ma_khoa_hoc'] ?? null,
            'hang_pmglx' => $match['hang_gplx'],
            'hang_dao_tao' => $match['hang_dao_tao'],
            'mon' => $monRows,
            'will_apply' => $willApply,
        ];

        if ($tt) {
            return array_merge($base, $shared, [
                'status' => 'matched',
                'message' => '',
                'thu_tien_id' => $tt->Id,
            ]);
        }

        return array_merge($base, $shared, [
            'status' => 'will_add_thu_tien',
            'message' => 'Khớp PMGPLX (HangGPLX '.$match['hang_gplx'].') — chưa có trong danh sách thu tiền, sẽ thêm khi xác nhận.',
            'thu_tien_id' => null,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, list<array{ma_dk: string, ma_khoa_hoc: ?string, hang_gplx: ?string, hang_dao_tao: ?string, cccd_norm: string}>>
     */
    private function buildPmglxIndexForRecords(array $records): array
    {
        $need = [];
        foreach ($records as $record) {
            $n = KetQuaSatHachExcelParser::normalizeCccd($record['so_dinh_danh'] ?? '');
            if ($n !== '') {
                $need[$n] = true;
            }
        }
        if ($need === []) {
            return [];
        }

        $index = [];
        foreach (array_chunk(array_keys($need), 40) as $chunk) {
            $query = NguoiLX::query()
                ->from('NguoiLX as n')
                ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
                ->whereNotNull('n.SoCMT')
                ->where('n.SoCMT', '!=', '')
                ->where(function ($q) use ($chunk): void {
                    foreach ($chunk as $cccd) {
                        $q->orWhere('n.SoCMT', 'like', '%'.$cccd);
                    }
                })
                ->get(['n.MaDK', 'n.SoCMT', 'h.MaKhoaHoc', 'h.HangGPLX', 'h.HangDaoTao']);

            foreach ($query as $hv) {
                $cccdNorm = KetQuaSatHachExcelParser::normalizeCccd($hv->SoCMT);
                if ($cccdNorm === '') {
                    continue;
                }
                foreach ($chunk as $fileCccd) {
                    if (! KetQuaSatHachExcelParser::cccdMatches($fileCccd, $cccdNorm)) {
                        continue;
                    }
                    $index[$fileCccd][] = [
                        'ma_dk' => (string) $hv->MaDK,
                        'ma_khoa_hoc' => $hv->MaKhoaHoc !== null ? (string) $hv->MaKhoaHoc : null,
                        'hang_gplx' => $hv->HangGPLX !== null ? (string) $hv->HangGPLX : null,
                        'hang_dao_tao' => $hv->HangDaoTao !== null ? (string) $hv->HangDaoTao : null,
                        'cccd_norm' => $cccdNorm,
                    ];
                }
            }
        }

        return $index;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $pmglxIndex
     * @return list<array<string, mixed>>
     */
    private function findPmglxCandidates(array $pmglxIndex, string $cccd, string $hangFile): array
    {
        $cccdNorm = KetQuaSatHachExcelParser::normalizeCccd($cccd);
        if ($cccdNorm === '') {
            return [];
        }

        $entries = $pmglxIndex[$cccdNorm] ?? [];
        if ($entries === []) {
            foreach ($pmglxIndex as $key => $list) {
                if (KetQuaSatHachExcelParser::cccdMatches($key, $cccdNorm)) {
                    $entries = array_merge($entries, $list);
                }
            }
        }

        $out = [];
        foreach ($entries as $e) {
            if (HangSatHachMatch::matches($hangFile, $e['hang_gplx'] ?? null, $e['hang_dao_tao'] ?? null)) {
                $out[] = $e;
            }
        }

        $uniq = [];
        foreach ($out as $e) {
            $uniq[$e['ma_dk']] = $e;
        }

        return array_values($uniq);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveThuTienForApply(array $row, ?int $nguoiId): ?ThuTienHocVien
    {
        $maDk = (string) ($row['ma_dk'] ?? '');
        if ($maDk === '') {
            return null;
        }

        $existing = ThuTienHocVien::query()->where('MaDK', $maDk)->first();
        if ($existing) {
            return $existing;
        }

        $maKh = trim((string) ($row['ma_khoa_hoc'] ?? ''));
        if ($maKh === '') {
            $maKh = (string) NguoiLX::query()
                ->from('NguoiLX as n')
                ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
                ->where('n.MaDK', $maDk)
                ->value('h.MaKhoaHoc');
        }

        $now = Carbon::now();

        return ThuTienHocVien::query()->create([
            'MaDK' => $maDk,
            'MaKhoaHoc' => $maKh !== '' ? $maKh : null,
            'DaTotNghiep' => false,
            'DaThuTien' => false,
            'NguoiTaoId' => $nguoiId,
            'NgayTao' => $now,
            'NgayCapNhat' => $now,
        ]);
    }

    /**
     * @return array{nhan: string, kq_raw: string, kq: ?string, lan: int, hien_tai: string, action: string, ghi_chu: string}
     */
    private function planMonMoi(string $khoan, ?string $targetKq, int $targetLan, string $kqRaw): array
    {
        $nhan = KetQuaSatHachExcelParser::MON[$khoan]['nhan'];
        if ($targetKq === null) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => null,
                'lan' => $targetLan,
                'hien_tai' => 'Chưa thi',
                'action' => 'bo_qua',
                'ghi_chu' => 'Chưa thi (—)',
            ];
        }

        return [
            'nhan' => $nhan,
            'kq_raw' => $kqRaw,
            'kq' => $targetKq,
            'lan' => $targetLan,
            'hien_tai' => 'Chưa thi',
            'action' => 'cap_nhat',
            'ghi_chu' => 'Ghi lần '.$targetLan.' · '.ThuTienHocVien::NHAN_KQ[$targetKq],
        ];
    }

    /**
     * @return array{
     *     nhan: string,
     *     kq_raw: string,
     *     kq: ?string,
     *     lan: int,
     *     hien_tai: string,
     *     action: string,
     *     ghi_chu: string
     * }
     */
    private function planMon(ThuTienHocVien $tt, string $khoan, ?string $targetKq, int $targetLan, string $kqRaw): array
    {
        $nhan = KetQuaSatHachExcelParser::MON[$khoan]['nhan'];
        $cot = ThuTienHocVien::COT_KET_QUA[$khoan];
        $currentKq = $tt->{$cot};
        $currentLan = $tt->soLanThi($khoan);
        $hienTai = $currentKq === null
            ? ($currentLan > 0 ? 'Đã có '.$currentLan.' lần thi' : 'Chưa thi')
            : $tt->nhanKetQua($khoan);

        if ($targetKq === null) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => null,
                'lan' => $targetLan,
                'hien_tai' => $hienTai,
                'action' => 'bo_qua',
                'ghi_chu' => 'Chưa thi (—)',
            ];
        }

        if ($currentKq === ThuTienHocVien::DAU) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => $targetKq,
                'lan' => $targetLan,
                'hien_tai' => $hienTai,
                'action' => 'bo_qua',
                'ghi_chu' => 'Đã đậu trước đó',
            ];
        }

        if ($currentLan > $targetLan) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => $targetKq,
                'lan' => $targetLan,
                'hien_tai' => $hienTai,
                'action' => 'bo_qua',
                'ghi_chu' => 'PM đã ghi nhiều lần thi hơn file',
            ];
        }

        if ($currentLan === $targetLan && $currentKq === $targetKq) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => $targetKq,
                'lan' => $targetLan,
                'hien_tai' => $hienTai,
                'action' => 'bo_qua',
                'ghi_chu' => 'Đã khớp',
            ];
        }

        if ($currentLan === $targetLan && $currentKq !== $targetKq && $currentLan > 0) {
            return [
                'nhan' => $nhan,
                'kq_raw' => $kqRaw,
                'kq' => $targetKq,
                'lan' => $targetLan,
                'hien_tai' => $hienTai,
                'action' => 'cap_nhat',
                'ghi_chu' => 'Sửa KQ lần '.$targetLan.' ('.ThuTienHocVien::NHAN_KQ[$currentKq ?? ''].' → '.ThuTienHocVien::NHAN_KQ[$targetKq].')',
            ];
        }

        return [
            'nhan' => $nhan,
            'kq_raw' => $kqRaw,
            'kq' => $targetKq,
            'lan' => $targetLan,
            'hien_tai' => $hienTai,
            'action' => 'cap_nhat',
            'ghi_chu' => 'Thêm đến lần '.$targetLan.' · '.ThuTienHocVien::NHAN_KQ[$targetKq],
        ];
    }

    /** @return int Số lần thi đã ghi thêm/sửa */
    private function applyMon(ThuTienHocVien $tt, string $khoan, string $targetKq, int $targetLan, ?int $nguoiId): int
    {
        $cot = ThuTienHocVien::COT_KET_QUA[$khoan];
        $count = 0;

        while ($tt->soLanThi($khoan) < $targetLan) {
            $nextLan = $tt->soLanThi($khoan) + 1;
            $kq = ($nextLan === $targetLan) ? $targetKq : ThuTienHocVien::ROT;
            $tt->themLanThi($khoan, $kq, null, $nguoiId);
            $count++;
            $tt->refresh();
            $tt->load('lanThis');
        }

        if ($tt->soLanThi($khoan) === $targetLan && $tt->{$cot} !== $targetKq) {
            $tt->huyLanThiCuoi($khoan, $nguoiId);
            $tt->themLanThi($khoan, $targetKq, null, $nguoiId);
            $count++;
        }

        return $count;
    }
}
