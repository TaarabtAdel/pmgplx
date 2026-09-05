<?php

namespace App\Services\PMGPLX;

use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Models\PMGPLX\NguoiLXHoSo;
use App\Models\PMGPLXOLD\KhoaHoc as KhoaHocOld;
use App\Support\PMGPLX\DonViCapGPLXBanCu;
use App\Support\PMGPLX\MaDkBanCu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DongBoHocVienBanCuService
{
    public const SESSION_LAST_BATCH = 'pmgplx.dong_bo_hoc_vien.last_batch';

    /**
     * @return Collection<int, object{
     *     MaDK: string,
     *     HoVaTen: ?string,
     *     HoDemNLX: ?string,
     *     TenNLX: ?string,
     *     NgaySinh: mixed,
     *     GioiTinh: ?string,
     *     SoGPLXDaCo: ?string,
     *     DonViCapGPLXDaCo: ?string,
     *     NgayTTGPLXDaCo: mixed,
     *     MaKhoaHoc: ?string,
     *     HangGPLX: ?string,
     *     TrangThai: ?string
     * }>
     */
    public function studentsInCourse(string $maKhoaHoc): Collection
    {
        return NguoiLX::query()
            ->from('NguoiLX as n')
            ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->where('h.MaKhoaHoc', $maKhoaHoc)
            ->orderBy('n.MaDK')
            ->get([
                'n.MaDK',
                'n.HoDemNLX',
                'n.TenNLX',
                'n.HoVaTen',
                'n.NgaySinh',
                'n.GioiTinh',
                'n.TrangThai',
                'h.SoGPLXDaCo',
                'h.DonViCapGPLXDaCo',
                'h.NgayTTGPLXDaCo',
                'h.MaKhoaHoc',
                'h.HangGPLX',
            ]);
    }

    /**
     * @return array{
     *     source: list<array<string, mixed>>,
     *     planned: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     */
    public function buildPreview(string $maKhoaHocNguon, ?string $maKhoaHocDich): array
    {
        $students = $this->studentsInCourse($maKhoaHocNguon);

        $source = [];
        $planned = [];
        $plannedMaDks = [];

        foreach ($students->values() as $row) {
            $hoTen = trim((string) ($row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''))));

            $soGplx = trim((string) ($row->SoGPLXDaCo ?? ''));
            $donViCapGoc = trim((string) ($row->DonViCapGPLXDaCo ?? ''));
            $dvcTuSoGplx = DonViCapGPLXBanCu::prefixFromSoGPLX($soGplx);
            $donViCapBanCu = DonViCapGPLXBanCu::mapForOldSoftware(
                $soGplx !== '' ? $soGplx : null,
                $row->NgayTTGPLXDaCo
            );

            $source[] = [
                'ma_dk' => $row->MaDK,
                'ho_ten' => $hoTen,
                'ngay_sinh' => $row->NgaySinh,
                'gioi_tinh' => $row->GioiTinh,
                'so_gplx_da_co' => $row->SoGPLXDaCo,
                'don_vi_cap_gplx_da_co' => $donViCapGoc,
                'ma_khoa_hoc' => $row->MaKhoaHoc,
                'hang_gplx' => $row->HangGPLX,
            ];

            $mappedMaDk = $maKhoaHocDich
                ? MaDkBanCu::mapFromSource((string) $row->MaDK, $maKhoaHocDich)
                : null;

            if ($mappedMaDk) {
                $plannedMaDks[] = $mappedMaDk;
            }

            $planned[] = [
                'ma_dk' => $mappedMaDk,
                'ma_dk_nguon' => $row->MaDK,
                'ho_ten' => $hoTen,
                'ngay_sinh' => $row->NgaySinh,
                'gioi_tinh' => $row->GioiTinh,
                'so_gplx_da_co' => $row->SoGPLXDaCo,
                'ngay_tt_gplx_da_co' => DonViCapGPLXBanCu::formatNgayTT($row->NgayTTGPLXDaCo),
                'ngay_tt_sau_moc' => DonViCapGPLXBanCu::isNgayTTAfterCutoff($row->NgayTTGPLXDaCo),
                'dvc_tu_so_gplx' => $dvcTuSoGplx,
                'don_vi_cap_gplx_da_co_goc' => $donViCapGoc,
                'don_vi_cap_gplx_da_co' => $donViCapBanCu,
                'don_vi_cap_doi' => $donViCapBanCu !== '' && $donViCapBanCu !== $donViCapGoc,
                'hang_gplx' => $row->HangGPLX,
                'ton_tai' => false,
            ];
        }

        $existingMaDks = $maKhoaHocDich ? $this->findExistingMaDks($plannedMaDks) : [];
        $conflicts = [];

        foreach ($planned as $index => &$row) {
            $maDk = $row['ma_dk'];
            if (! $maDk) {
                continue;
            }

            if (isset($existingMaDks[$maDk])) {
                $row['ton_tai'] = true;
                $conflicts[] = [
                    'ma_dk' => $maDk,
                    'ma_dk_nguon' => $row['ma_dk_nguon'],
                    'ho_ten' => $row['ho_ten'],
                ];
            }
        }
        unset($row);

        $khoaNguon = KhoaHoc::query()->find($maKhoaHocNguon, ['MaKH', 'TenKH']);
        $khoaDich = $maKhoaHocDich
            ? KhoaHocOld::query()->find($maKhoaHocDich, ['MaKH', 'TenKH'])
            : null;

        $prefixNguon = MaDkBanCu::prefixFromMaKhoaHoc($maKhoaHocNguon);
        $prefixDich = $maKhoaHocDich ? MaDkBanCu::prefixFromMaKhoaHoc($maKhoaHocDich) : null;

        return [
            'source' => $source,
            'planned' => $planned,
            'meta' => [
                'count' => $students->count(),
                'ma_kh_nguon' => $maKhoaHocNguon,
                'ten_kh_nguon' => $khoaNguon?->TenKH,
                'ma_kh_dich' => $maKhoaHocDich,
                'ten_kh_dich' => $khoaDich?->TenKH,
                'ma_dk_prefix_nguon' => $prefixNguon,
                'ma_dk_prefix_dich' => $prefixDich,
                'conflict_count' => count($conflicts),
                'update_count' => count($conflicts),
                'conflicts' => $conflicts,
                'syncable_count' => count(array_filter(
                    $planned,
                    fn ($row) => ! empty($row['ma_dk'])
                )),
                'test_student' => $this->firstTestableStudent($planned),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $planned
     * @return array<string, mixed>|null
     */
    public function firstTestableStudent(array $planned): ?array
    {
        foreach ($planned as $row) {
            if (empty($row['ma_dk']) || empty($row['ma_dk_nguon'])) {
                continue;
            }

            return [
                'ma_dk_nguon' => $row['ma_dk_nguon'],
                'ma_dk' => $row['ma_dk'],
                'ho_ten' => $row['ho_ten'],
            ];
        }

        return null;
    }

    /**
     * @return array{
     *     inserted: int,
     *     updated: int,
     *     failed: int,
     *     total: int,
     *     skipped: int,
     *     inserted_ma_dks: list<string>,
     *     source_ma_dk?: string|null,
     *     target_ma_dk?: string|null,
     *     ho_ten?: string
     * }
     */
    public function sync(string $maKhoaHocNguon, string $maKhoaHocDich, ?string $onlySourceMaDk = null): array
    {
        $students = $this->studentsInCourse($maKhoaHocNguon);

        if ($onlySourceMaDk !== null) {
            $students = $students
                ->filter(fn ($row) => (string) $row->MaDK === $onlySourceMaDk)
                ->values();
        }

        if ($students->isEmpty()) {
            return ['inserted' => 0, 'updated' => 0, 'failed' => 0, 'total' => 0, 'skipped' => 0, 'inserted_ma_dks' => []];
        }

        if (! KhoaHocOld::query()->where('MaKH', $maKhoaHocDich)->exists()) {
            throw new \InvalidArgumentException('Mã khóa học đích không tồn tại trên bản cũ.');
        }

        $plannedMaDks = $students
            ->map(fn ($row) => MaDkBanCu::mapFromSource((string) $row->MaDK, $maKhoaHocDich))
            ->all();

        $existingMaDks = $this->findExistingMaDks($plannedMaDks);

        $maDks = $students->pluck('MaDK')->all();

        [$colsNguoi, $colsHoSo, $colsGiayTo] = $this->mappableColumns();

        $nguoiRows = NguoiLX::query()->whereIn('MaDK', $maDks)->get()->keyBy('MaDK');
        $hoSoRows = NguoiLXHoSo::query()->whereIn('MaDK', $maDks)->get()->keyBy('MaDK');
        $giayToRows = DB::connection('sqlsrv')
            ->table('NguoiLXHS_GiayTo')
            ->whereIn('MaDK', $maDks)
            ->get()
            ->groupBy('MaDK');

        $oldDb = DB::connection('sqlsrv_old');
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $syncedMaDks = [];

        $oldDb->beginTransaction();

        try {
            foreach ($students->values() as $index => $row) {
                $sourceMaDk = $row->MaDK;
                $targetMaDk = $plannedMaDks[$index] ?? MaDkBanCu::mapFromSource((string) $sourceMaDk, $maKhoaHocDich);

                if (! $targetMaDk) {
                    $failed++;
                    continue;
                }

                $nguoi = $nguoiRows->get($sourceMaDk);
                if (! $nguoi) {
                    $failed++;
                    continue;
                }

                $exists = isset($existingMaDks[$targetMaDk]);

                $payloadNguoi = $this->onlyColumns($nguoi->getAttributes(), $colsNguoi);
                $payloadNguoi['MaDK'] = $targetMaDk;
                $oldDb->table('NguoiLX')->updateOrInsert(['MaDK' => $targetMaDk], $payloadNguoi);

                $hoSo = $hoSoRows->get($sourceMaDk);
                if ($hoSo) {
                    $payloadHoSo = $this->mapHoSoPayloadForBanCu(
                        $this->onlyColumns($hoSo->getAttributes(), $colsHoSo)
                    );
                    $payloadHoSo['MaDK'] = $targetMaDk;
                    $payloadHoSo['MaKhoaHoc'] = $maKhoaHocDich;
                    $oldDb->table('NguoiLX_HoSo')->updateOrInsert(['MaDK' => $targetMaDk], $payloadHoSo);
                }

                $this->syncGiayToRows(
                    $oldDb,
                    $giayToRows->get($sourceMaDk, collect()),
                    $targetMaDk,
                    $colsGiayTo
                );

                $syncedMaDks[] = $targetMaDk;
                $exists ? $updated++ : $inserted++;
            }

            $oldDb->commit();
        } catch (\Throwable $e) {
            $oldDb->rollBack();

            throw $e;
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed,
            'total' => $students->count(),
            'skipped' => 0,
            'inserted_ma_dks' => $syncedMaDks,
            'source_ma_dk' => $onlySourceMaDk ?? ($students->first()->MaDK ?? null),
            'target_ma_dk' => $syncedMaDks[0] ?? null,
            'ho_ten' => $students->isEmpty()
                ? ''
                : trim((string) ($students->first()->HoVaTen ?: trim(($students->first()->HoDemNLX ?? '').' '.($students->first()->TenNLX ?? '')))),
        ];
    }

    /**
     * @param  list<string>  $maDks
     * @return array<string, true>
     */
    public function findExistingMaDks(array $maDks): array
    {
        $maDks = array_values(array_unique(array_filter($maDks)));

        if ($maDks === []) {
            return [];
        }

        return DB::connection('sqlsrv_old')
            ->table('NguoiLX')
            ->whereIn('MaDK', $maDks)
            ->pluck('MaDK')
            ->mapWithKeys(fn ($maDk) => [(string) $maDk => true])
            ->all();
    }

    /**
     * Khôi phục = xóa học viên đã đồng bộ khỏi bản cũ.
     *
     * @param  array{ma_kh_nguon?: string, ma_kh_dich?: string, ma_dks?: list<string>}  $batch
     * @return array{deleted: int, total: int}
     */
    public function restore(array $batch): array
    {
        $maDks = array_values(array_unique(array_filter($batch['ma_dks'] ?? [])));

        if ($maDks === []) {
            throw new \InvalidArgumentException('Không có dữ liệu để khôi phục.');
        }

        $oldDb = DB::connection('sqlsrv_old');
        $deleted = 0;

        $oldDb->beginTransaction();

        try {
            foreach (array_chunk($maDks, 500) as $chunk) {
                $oldDb->table('NguoiLXHS_GiayTo')->whereIn('MaDK', $chunk)->delete();
                $oldDb->table('NguoiLX_HoSo')->whereIn('MaDK', $chunk)->delete();
                $deleted += $oldDb->table('NguoiLX')->whereIn('MaDK', $chunk)->delete();
            }

            $oldDb->commit();
        } catch (\Throwable $e) {
            $oldDb->rollBack();

            throw $e;
        }

        return [
            'deleted' => $deleted,
            'total' => count($maDks),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function buildSessionBatch(string $maKhoaHocNguon, string $maKhoaHocDich, array $result, bool $isTest = false): array
    {
        return [
            'ma_kh_nguon' => $maKhoaHocNguon,
            'ma_kh_dich' => $maKhoaHocDich,
            'ma_dks' => $result['inserted_ma_dks'] ?? [],
            'count' => (int) (($result['inserted'] ?? 0) + ($result['updated'] ?? 0)),
            'synced_at' => now()->format('d/m/Y H:i:s'),
            'is_test' => $isTest,
            'ho_ten' => $result['ho_ten'] ?? null,
            'source_ma_dk' => $result['source_ma_dk'] ?? null,
            'target_ma_dk' => $result['target_ma_dk'] ?? null,
        ];
    }

    /**
     * Cột dùng khi copy sang bản cũ (giao 2 DB, bỏ cột IDENTITY trên sqlsrv_old).
     *
     * @return array{0: list<string>, 1: list<string>, 2: list<string>}
     */
    public function mappableColumns(): array
    {
        return [
            $this->commonColumns('NguoiLX'),
            $this->commonColumns('NguoiLX_HoSo'),
            $this->commonColumns('NguoiLXHS_GiayTo'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function mapHoSoPayloadForBanCu(array $payload): array
    {
        if (array_key_exists('DonViCapGPLXDaCo', $payload)) {
            $payload['DonViCapGPLXDaCo'] = DonViCapGPLXBanCu::mapForOldSoftware(
                isset($payload['SoGPLXDaCo']) ? (string) $payload['SoGPLXDaCo'] : null,
                $payload['NgayTTGPLXDaCo'] ?? null
            );
        }

        return $payload;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @param  list<string>  $columns
     */
    private function syncGiayToRows($oldDb, Collection $rows, string $targetMaDk, array $columns): void
    {
        foreach ($rows as $row) {
            $payload = $this->onlyColumns((array) $row, $columns);
            $payload['MaDK'] = $targetMaDk;
            $maGt = $payload['MaGT'] ?? null;

            if ($maGt === null) {
                continue;
            }

            $oldDb->table('NguoiLXHS_GiayTo')->updateOrInsert(
                ['MaDK' => $targetMaDk, 'MaGT' => $maGt],
                $payload
            );
        }
    }

    /**
     * @return list<string>
     */
    private function commonColumns(string $table): array
    {
        $cols = array_values(array_intersect(
            Schema::connection('sqlsrv')->getColumnListing($table),
            Schema::connection('sqlsrv_old')->getColumnListing($table)
        ));

        return array_values(array_diff($cols, $this->identityColumns('sqlsrv_old', $table)));
    }

    /**
     * @return list<string>
     */
    private function identityColumns(string $connection, string $table): array
    {
        static $cache = [];

        $key = $connection.'::'.$table;
        if (! array_key_exists($key, $cache)) {
            $rows = DB::connection($connection)->select(
                'SELECT c.name FROM sys.columns c INNER JOIN sys.tables t ON c.object_id = t.object_id WHERE t.name = ? AND c.is_identity = 1',
                [$table]
            );
            $cache[$key] = array_map(static fn ($row) => (string) $row->name, $rows);
        }

        return $cache[$key];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function onlyColumns(array $attributes, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            if (! array_key_exists($col, $attributes)) {
                continue;
            }
            $val = $attributes[$col];
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }
            $out[$col] = $val;
        }

        return $out;
    }
}
