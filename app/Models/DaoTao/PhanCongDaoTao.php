<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Throwable;

class PhanCongDaoTao extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'PhanCongDaoTao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'GiaoVienId',
        'XeTapLaiId',
        'KhoaDaoTaoId',
        'TuNgay',
        'DenNgay',
        'LoaiGiangDay',
        'NoiDungGiangDay',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'TuNgay' => 'date',
        'DenNgay' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function giaoVien(): BelongsTo
    {
        return $this->belongsTo(GiaoVien::class, 'GiaoVienId');
    }

    public function xeTapLai(): BelongsTo
    {
        return $this->belongsTo(XeTapLai::class, 'XeTapLaiId');
    }

    public function khoaDaoTao(): BelongsTo
    {
        return $this->belongsTo(KhoaDaoTao::class, 'KhoaDaoTaoId');
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array{saved: int, updated: int, created: int, khoa_count: int, gv_created: int, xe_created: int, errors: list<string>}
     *
     * @throws Throwable
     */
    public static function importFromPreview(array $preview): array
    {
        $records = self::saveableRecords($preview['records'] ?? []);
        if ($records === []) {
            return [
                'saved' => 0,
                'updated' => 0,
                'created' => 0,
                'khoa_count' => 0,
                'gv_created' => 0,
                'xe_created' => 0,
                'errors' => ['Không có dòng nào đủ điều kiện lưu (cần giáo viên và loại lý thuyết/GVLT hoặc thực hành/GVTH).'],
            ];
        }

        $errors = self::collectRowErrors($records);
        if ($errors !== []) {
            return ['saved' => 0, 'updated' => 0, 'created' => 0, 'khoa_count' => 0, 'gv_created' => 0, 'xe_created' => 0, 'errors' => $errors];
        }

        $saved = 0;
        $updated = 0;
        $created = 0;
        $gvCreated = 0;
        $xeCreated = 0;
        $khoaIds = [];

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($records, &$saved, &$updated, &$created, &$gvCreated, &$xeCreated, &$khoaIds): void {
            /** @var array<string, list<array<string, mixed>>> $byKhoa */
            $byKhoa = [];
            foreach ($records as $record) {
                if (! self::isSaveableRecord($record)) {
                    continue;
                }
                $tenKhoa = KhoaDaoTao::normalizeTenKhoa((string) ($record['TenKhoa'] ?? ''));
                $byKhoa[$tenKhoa][] = $record;
            }

            foreach ($byKhoa as $tenKhoa => $khoaRecords) {
                $khoa = KhoaDaoTao::findOrCreateByTenKhoa($tenKhoa);
                $khoaId = (int) $khoa->Id;
                $khoaIds[] = $khoaId;
                $keptIds = [];

                foreach ($khoaRecords as $record) {
                    $hoTen = trim((string) ($record['HoTen'] ?? ''));
                    $before = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->exists();
                    $gv = GiaoVien::findOrCreateByHoTen($hoTen, self::guessLoaiGv((string) ($record['NoiDungGiangDay'] ?? '')));
                    if ($gv === null) {
                        continue;
                    }
                    $giaoVienId = $gv->Id;
                    if (! $before) {
                        $gvCreated++;
                    }

                    $xeTapLaiId = null;
                    $bienSo = trim((string) ($record['BienSo'] ?? ''));
                    if ($bienSo !== '') {
                        $normalized = XeTapLai::normalizeBienSo($bienSo);
                        $beforeXe = XeTapLai::query()->where('BienSo', $normalized)->exists();
                        $xe = XeTapLai::findOrCreateByBienSo($bienSo, self::guessLoaiXe((string) ($record['NoiDungGiangDay'] ?? '')));
                        $xeTapLaiId = $xe->Id;
                        if (! $beforeXe) {
                            $xeCreated++;
                        }
                    }

                    $loaiGiangDay = self::classifyLoaiGiangDay((string) ($record['NoiDungGiangDay'] ?? ''));
                    if ($loaiGiangDay === null) {
                        continue;
                    }

                    $tuNgay = (string) $record['TuNgay'];
                    $denNgay = (string) $record['DenNgay'];
                    if ($tuNgay > $denNgay) {
                        [$tuNgay, $denNgay] = [$denNgay, $tuNgay];
                    }

                    $data = [
                        'GiaoVienId' => $giaoVienId,
                        'XeTapLaiId' => $xeTapLaiId,
                        'TuNgay' => $tuNgay,
                        'DenNgay' => $denNgay,
                        'LoaiGiangDay' => $loaiGiangDay,
                        'NoiDungGiangDay' => null,
                        'GhiChu' => self::nullableLimit($record['GhiChu'] ?? null, 255),
                    ];

                    $existing = static::query()
                        ->where('KhoaDaoTaoId', $khoaId)
                        ->where(self::rowMatchQuery($record, $giaoVienId, $xeTapLaiId))
                        ->first();

                    if ($existing !== null) {
                        $existing->update(array_merge($data, ['NgayCapNhat' => now()]));
                        $keptIds[] = (int) $existing->Id;
                        $updated++;
                    } else {
                        $row = static::query()->create(array_merge($data, [
                            'KhoaDaoTaoId' => $khoaId,
                            'NgayTao' => now(),
                        ]));
                        $keptIds[] = (int) $row->Id;
                        $created++;
                    }

                    $saved++;
                }

                $orphanQuery = static::query()->where('KhoaDaoTaoId', $khoaId);
                if ($keptIds !== []) {
                    $orphanQuery->whereNotIn('Id', $keptIds);
                }
                $orphanQuery->delete();
            }
        });

        return [
            'saved' => $saved,
            'updated' => $updated,
            'created' => $created,
            'khoa_count' => count(array_unique($khoaIds)),
            'gv_created' => $gvCreated,
            'xe_created' => $xeCreated,
            'errors' => [],
        ];
    }

    /**
     * Khóa đối chiếu 1 dòng phân công trong cùng khoá.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private static function rowMatchQuery(array $record, ?int $giaoVienId, ?int $xeTapLaiId): array
    {
        return [
            'TuNgay' => $record['TuNgay'],
            'DenNgay' => $record['DenNgay'],
            'GiaoVienId' => $giaoVienId,
            'XeTapLaiId' => $xeTapLaiId,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<string>
     */
    public static function collectRowErrors(array $records): array
    {
        $errors = [];
        foreach ($records as $i => $record) {
            if (! self::isSaveableRecord($record)) {
                continue;
            }

            $line = (int) ($record['excel_row'] ?? ($i + 2));
            $tenKhoa = trim((string) ($record['TenKhoa'] ?? ''));

            if ($tenKhoa === '') {
                $errors[] = "Dòng {$line}: thiếu khoá đào tạo.";
                continue;
            }
            if (empty($record['TuNgay']) || empty($record['DenNgay'])) {
                $errors[] = "Dòng {$line}: không parse được thời gian.";
                continue;
            }
            if ((string) $record['TuNgay'] > (string) $record['DenNgay']) {
                $errors[] = "Dòng {$line}: ngày kết thúc trước ngày bắt đầu.";
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    public static function saveableRecords(array $records): array
    {
        return array_values(array_filter($records, fn (array $record): bool => self::isSaveableRecord($record)));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public static function isSaveableRecord(array $record): bool
    {
        if (trim((string) ($record['HoTen'] ?? '')) === '') {
            return false;
        }

        $noiDung = (string) ($record['NoiDungGiangDay'] ?? '');
        if (self::isTuDongNoiDung($noiDung)) {
            return false;
        }

        return self::classifyLoaiGiangDay($noiDung) !== null;
    }

    public static function classifyLoaiGiangDay(string $noiDung): ?string
    {
        if (self::isTuDongNoiDung($noiDung)) {
            return null;
        }

        $upper = mb_strtoupper($noiDung);
        $lower = mb_strtolower($noiDung);
        if (str_contains($lower, 'lý thuyết') || str_contains($lower, 'ly thuyet') || str_contains($upper, 'GVLT')) {
            return 'ly_thuyet';
        }

        if (str_contains($lower, 'thực hành') || str_contains($lower, 'thuc hanh') || str_contains($upper, 'GVTH')) {
            return 'thuc_hanh';
        }

        return null;
    }

    public static function skipReason(array $record): ?string
    {
        if (trim((string) ($record['HoTen'] ?? '')) === '') {
            return 'Không có giáo viên';
        }

        $noiDung = (string) ($record['NoiDungGiangDay'] ?? '');
        if (self::isTuDongNoiDung($noiDung)) {
            return 'Xe tự động — không lưu';
        }

        if (self::classifyLoaiGiangDay($noiDung) === null) {
            return 'Không xác định loại (cần lý thuyết/GVLT hoặc thực hành/GVTH)';
        }

        return null;
    }

    public static function loaiGiangDayLabel(?string $loai): string
    {
        return match ($loai) {
            'ly_thuyet' => 'Lý thuyết',
            'thuc_hanh' => 'Thực hành',
            default => '—',
        };
    }

    private static function isTuDongNoiDung(string $noiDung): bool
    {
        $lower = mb_strtolower($noiDung);

        return str_contains($lower, 'tự động') || str_contains($lower, 'tu dong');
    }

    /**
     * Cảnh báo trùng khoảng thời gian của cùng giáo viên giữa các khoá khác nhau.
     *
     * @param  list<int>  $giaoVienIds
     * @return list<string>
     */
    public static function crossKhoaOverlapWarningsForGiaoVienIds(array $giaoVienIds): array
    {
        if ($giaoVienIds === []) {
            return [];
        }

        $rows = static::query()
            ->with(['khoaDaoTao', 'giaoVien'])
            ->whereIn('GiaoVienId', $giaoVienIds)
            ->whereNotNull('TuNgay')
            ->whereNotNull('DenNgay')
            ->orderBy('GiaoVienId')
            ->orderBy('TuNgay')
            ->get();

        $warnings = [];
        $seenPairs = [];

        foreach ($rows->groupBy('GiaoVienId') as $gvRows) {
            $list = $gvRows->values()->all();
            $count = count($list);

            for ($a = 0; $a < $count; $a++) {
                for ($b = $a + 1; $b < $count; $b++) {
                    $rowA = $list[$a];
                    $rowB = $list[$b];

                    if ((int) $rowA->KhoaDaoTaoId === (int) $rowB->KhoaDaoTaoId) {
                        continue;
                    }

                    if (! self::rangesOverlap(
                        $rowA->TuNgay->toDateString(),
                        $rowA->DenNgay->toDateString(),
                        $rowB->TuNgay->toDateString(),
                        $rowB->DenNgay->toDateString()
                    )) {
                        continue;
                    }

                    $pairKey = min((int) $rowA->Id, (int) $rowB->Id).'-'.max((int) $rowA->Id, (int) $rowB->Id);
                    if (isset($seenPairs[$pairKey])) {
                        continue;
                    }
                    $seenPairs[$pairKey] = true;

                    $hoTen = $rowA->giaoVien?->HoTen ?? '—';
                    $khoaA = $rowA->khoaDaoTao?->TenKhoa ?? '—';
                    $khoaB = $rowB->khoaDaoTao?->TenKhoa ?? '—';
                    $rangeA = $rowA->TuNgay->format('d/m/Y').' – '.$rowA->DenNgay->format('d/m/Y');
                    $rangeB = $rowB->TuNgay->format('d/m/Y').' – '.$rowB->DenNgay->format('d/m/Y');

                    $warnings[] = "{$hoTen}: khoá {$khoaA} ({$rangeA}) trùng thời gian với khoá {$khoaB} ({$rangeB}).";
                }
            }
        }

        return $warnings;
    }

    /**
     * Cảnh báo trùng khoảng thời gian của cùng xe giữa các khoá khác nhau.
     * Trùng thời gian trong cùng một khoá được bỏ qua.
     *
     * @param  list<int>  $xeTapLaiIds
     * @return list<string>
     */
    public static function crossKhoaOverlapWarningsForXeTapLaiIds(array $xeTapLaiIds): array
    {
        if ($xeTapLaiIds === []) {
            return [];
        }

        $rows = static::query()
            ->with(['khoaDaoTao', 'xeTapLai'])
            ->whereIn('XeTapLaiId', $xeTapLaiIds)
            ->whereNotNull('TuNgay')
            ->whereNotNull('DenNgay')
            ->orderBy('XeTapLaiId')
            ->orderBy('TuNgay')
            ->get();

        $warnings = [];
        $seenPairs = [];

        foreach ($rows->groupBy('XeTapLaiId') as $xeRows) {
            $list = $xeRows->values()->all();
            $count = count($list);

            for ($a = 0; $a < $count; $a++) {
                for ($b = $a + 1; $b < $count; $b++) {
                    $rowA = $list[$a];
                    $rowB = $list[$b];

                    if ((int) $rowA->KhoaDaoTaoId === (int) $rowB->KhoaDaoTaoId) {
                        continue;
                    }

                    if (! self::rangesOverlap(
                        $rowA->TuNgay->toDateString(),
                        $rowA->DenNgay->toDateString(),
                        $rowB->TuNgay->toDateString(),
                        $rowB->DenNgay->toDateString()
                    )) {
                        continue;
                    }

                    $pairKey = min((int) $rowA->Id, (int) $rowB->Id).'-'.max((int) $rowA->Id, (int) $rowB->Id);
                    if (isset($seenPairs[$pairKey])) {
                        continue;
                    }
                    $seenPairs[$pairKey] = true;

                    $bienSo = $rowA->xeTapLai?->BienSo ?? '—';
                    $khoaA = $rowA->khoaDaoTao?->TenKhoa ?? '—';
                    $khoaB = $rowB->khoaDaoTao?->TenKhoa ?? '—';
                    $rangeA = $rowA->TuNgay->format('d/m/Y').' – '.$rowA->DenNgay->format('d/m/Y');
                    $rangeB = $rowB->TuNgay->format('d/m/Y').' – '.$rowB->DenNgay->format('d/m/Y');

                    $warnings[] = "{$bienSo}: khoá {$khoaA} ({$rangeA}) trùng thời gian với khoá {$khoaB} ({$rangeB}).";
                }
            }
        }

        return $warnings;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<string>
     */
    public static function validateOverlaps(array $records): array
    {
        $errors = [];
        $items = [];

        foreach ($records as $i => $record) {
            if (! self::isSaveableRecord($record)) {
                continue;
            }

            $items[] = [
                'index' => $i,
                'line' => (int) ($record['excel_row'] ?? ($i + 2)),
                'label' => self::recordLabel($record),
                'tu' => (string) $record['TuNgay'],
                'den' => (string) $record['DenNgay'],
                'gv_key' => self::gvKey($record),
                'xe_key' => self::xeKey($record),
                'ten_khoa' => KhoaDaoTao::normalizeTenKhoa((string) ($record['TenKhoa'] ?? '')),
            ];
        }

        for ($a = 0; $a < count($items); $a++) {
            for ($b = $a + 1; $b < count($items); $b++) {
                if (! self::rangesOverlap($items[$a]['tu'], $items[$a]['den'], $items[$b]['tu'], $items[$b]['den'])) {
                    continue;
                }
                if ($items[$a]['gv_key'] !== null && $items[$a]['gv_key'] === $items[$b]['gv_key']) {
                    $errors[] = "Dòng {$items[$a]['line']} và {$items[$b]['line']}: giáo viên trùng khoảng thời gian ({$items[$a]['label']}).";
                }
                if ($items[$a]['xe_key'] !== null && $items[$a]['xe_key'] === $items[$b]['xe_key']) {
                    $errors[] = "Dòng {$items[$a]['line']} và {$items[$b]['line']}: xe trùng khoảng thời gian ({$items[$a]['label']}).";
                }
            }
        }

        $khoaNames = array_values(array_unique(array_column($items, 'ten_khoa')));
        $khoaIds = KhoaDaoTao::query()->whereIn('TenKhoa', $khoaNames)->pluck('Id');

        $gvIdsInFile = [];
        $xeIdsInFile = [];
        foreach ($records as $record) {
            if (! self::isSaveableRecord($record)) {
                continue;
            }

            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            if ($hoTen !== '') {
                $gv = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->first();
                if ($gv !== null) {
                    $gvIdsInFile[] = $gv->Id;
                }
            }
            $bienSo = trim((string) ($record['BienSo'] ?? ''));
            if ($bienSo !== '') {
                $xe = XeTapLai::query()->where('BienSo', XeTapLai::normalizeBienSo($bienSo))->first();
                if ($xe !== null) {
                    $xeIdsInFile[] = $xe->Id;
                }
            }
        }

        if ($gvIdsInFile === [] && $xeIdsInFile === []) {
            return array_values(array_unique($errors));
        }

        $existingQuery = static::query()
            ->with(['giaoVien', 'xeTapLai', 'khoaDaoTao'])
            ->whereNotIn('KhoaDaoTaoId', $khoaIds->all());

        $existingQuery->where(function ($query) use ($gvIdsInFile, $xeIdsInFile): void {
            if ($gvIdsInFile !== []) {
                $query->whereIn('GiaoVienId', $gvIdsInFile);
            }
            if ($xeIdsInFile !== []) {
                $method = $gvIdsInFile !== [] ? 'orWhereIn' : 'whereIn';
                $query->{$method}('XeTapLaiId', $xeIdsInFile);
            }
        });

        $existing = $existingQuery->get();

        foreach ($items as $item) {
            $record = $records[$item['index']];
            $gvId = null;
            $xeId = null;
            $hoTen = trim((string) ($record['HoTen'] ?? ''));
            if ($hoTen !== '') {
                $gvId = GiaoVien::query()->where('HoTen', GiaoVien::normalizeHoTen($hoTen))->value('Id');
            }
            $bienSo = trim((string) ($record['BienSo'] ?? ''));
            if ($bienSo !== '') {
                $xeId = XeTapLai::query()->where('BienSo', XeTapLai::normalizeBienSo($bienSo))->value('Id');
            }

            foreach ($existing as $row) {
                if (! self::rangesOverlap($item['tu'], $item['den'], $row->TuNgay->toDateString(), $row->DenNgay->toDateString())) {
                    continue;
                }
                if ($gvId !== null && (int) $row->GiaoVienId === (int) $gvId) {
                    $errors[] = "Dòng {$item['line']}: giáo viên {$item['label']} trùng lịch với khoá {$row->khoaDaoTao?->TenKhoa} ({$row->TuNgay->format('d/m/Y')} – {$row->DenNgay->format('d/m/Y')}).";
                }
                if ($xeId !== null && (int) $row->XeTapLaiId === (int) $xeId) {
                    $errors[] = "Dòng {$item['line']}: xe trùng lịch với khoá {$row->khoaDaoTao?->TenKhoa} ({$row->TuNgay->format('d/m/Y')} – {$row->DenNgay->format('d/m/Y')}).";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function recordLabel(array $record): string
    {
        $parts = array_filter([
            trim((string) ($record['HoTen'] ?? '')),
            trim((string) ($record['BienSo'] ?? '')),
            trim((string) ($record['TenKhoa'] ?? '')),
        ]);

        return implode(' / ', $parts);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function gvKey(array $record): ?string
    {
        $hoTen = trim((string) ($record['HoTen'] ?? ''));
        if ($hoTen === '') {
            return null;
        }

        return mb_strtolower(GiaoVien::normalizeHoTen($hoTen));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function xeKey(array $record): ?string
    {
        $bienSo = trim((string) ($record['BienSo'] ?? ''));
        if ($bienSo === '') {
            return null;
        }

        return mb_strtolower(XeTapLai::normalizeBienSo($bienSo));
    }

    private static function rangesOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        return $aStart <= $bEnd && $aEnd >= $bStart;
    }

    private static function guessLoaiGv(string $noiDung): ?string
    {
        $upper = mb_strtoupper($noiDung);
        if (str_contains($upper, 'GVLT')) {
            return 'GVLT';
        }
        if (str_contains($upper, 'GVTH')) {
            return 'GVTH';
        }

        return null;
    }

    private static function guessLoaiXe(string $noiDung): ?string
    {
        if (mb_stripos($noiDung, 'tự động') !== false || mb_stripos($noiDung, 'tu dong') !== false) {
            return 'Số tự động';
        }

        return null;
    }

    private static function nullableLimit(mixed $value, int $maxLen): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return mb_strlen($text) <= $maxLen ? $text : mb_substr($text, 0, $maxLen);
    }
}
