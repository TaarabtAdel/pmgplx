<?php

namespace App\Models\DaoTao;

use App\Support\HoTenVietNam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class GiaoVien extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'GiaoVien';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaGV',
        'HoTen',
        'LoaiGV',
        'SoDienThoai',
        'TrangThai',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'TrangThai' => 'boolean',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function phanCong(): HasMany
    {
        return $this->hasMany(PhanCongDaoTao::class, 'GiaoVienId');
    }

    public static function findOrCreateByHoTen(string $hoTen, ?string $loaiGv = null): ?self
    {
        $hoTen = self::normalizeHoTen($hoTen);
        if ($hoTen === '') {
            return null;
        }

        $gv = static::query()->where('HoTen', $hoTen)->first();
        if ($gv !== null) {
            if ($loaiGv !== null && $gv->LoaiGV === null) {
                $gv->update(['LoaiGV' => $loaiGv, 'NgayCapNhat' => now()]);
            }

            return $gv;
        }

        return static::query()->create([
            'HoTen' => $hoTen,
            'LoaiGV' => $loaiGv,
            'TrangThai' => true,
            'NgayTao' => now(),
        ]);
    }

    public static function normalizeHoTen(string $hoTen): string
    {
        $hoTen = preg_replace('/\s+/u', ' ', trim($hoTen)) ?? trim($hoTen);

        return $hoTen;
    }

    /**
     * @param  array{tu_khoa?: string, loai_gv?: string, trang_thai?: string}  $filters
     * @return Builder<static>
     */
    public static function filteredQuery(array $filters): Builder
    {
        $query = static::query()->withCount('phanCong');

        $tuKhoa = trim((string) ($filters['tu_khoa'] ?? ''));
        if ($tuKhoa !== '') {
            $query->where(function ($q) use ($tuKhoa): void {
                $q->where('MaGV', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('HoTen', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('SoDienThoai', 'like', '%'.$tuKhoa.'%')
                    ->orWhere('LoaiGV', 'like', '%'.$tuKhoa.'%');
            });
        }

        $loaiGv = trim((string) ($filters['loai_gv'] ?? ''));
        if ($loaiGv !== '') {
            $query->where('LoaiGV', $loaiGv);
        }

        $trangThai = (string) ($filters['trang_thai'] ?? '');
        if ($trangThai !== '') {
            $query->where('TrangThai', (int) $trangThai);
        }

        return $query->orderBy('HoTen');
    }

    /**
     * @param  list<string>  $columns
     * @return Collection<int, static>
     */
    public static function allOrderedByVietnameseName(array $columns = ['Id', 'HoTen']): Collection
    {
        return static::query()
            ->get($columns)
            ->sort(fn (self $a, self $b): int => HoTenVietNam::compare($a->HoTen, $b->HoTen))
            ->values();
    }

    /**
     * Gộp các giáo viên trùng vào một bản ghi, chuyển PhanCongDaoTao sang ID giữ lại.
     *
     * @param  list<int>  $mergeIds
     * @return array{ok: bool, error?: string, merged_count?: int, phan_cong_updated?: int, keep_id?: int}
     *
     * @throws Throwable
     */
    public static function mergeRecords(int $keepId, array $mergeIds): array
    {
        $mergeIds = array_values(array_unique(array_filter(
            array_map('intval', $mergeIds),
            fn (int $id): bool => $id > 0 && $id !== $keepId
        )));

        if ($mergeIds === []) {
            return ['ok' => false, 'error' => 'Chọn ít nhất một giáo viên khác để gộp (không tính bản ghi giữ lại).'];
        }

        $keep = static::query()->find($keepId);
        if ($keep === null) {
            return ['ok' => false, 'error' => 'Không tìm thấy giáo viên giữ lại.'];
        }

        $sources = static::query()->whereIn('Id', $mergeIds)->get();
        if ($sources->count() !== count($mergeIds)) {
            return ['ok' => false, 'error' => 'Một hoặc nhiều giáo viên cần gộp không tồn tại.'];
        }

        $phanCongUpdated = 0;

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($keep, $sources, $mergeIds, &$phanCongUpdated): void {
            $phanCongUpdated = PhanCongDaoTao::query()
                ->whereIn('GiaoVienId', $mergeIds)
                ->update([
                    'GiaoVienId' => $keep->Id,
                    'NgayCapNhat' => now(),
                ]);

            foreach ($sources as $source) {
                $updates = [];
                if (($keep->MaGV === null || $keep->MaGV === '') && ($source->MaGV ?? '') !== '') {
                    $updates['MaGV'] = $source->MaGV;
                }
                if (($keep->LoaiGV === null || $keep->LoaiGV === '') && ($source->LoaiGV ?? '') !== '') {
                    $updates['LoaiGV'] = $source->LoaiGV;
                }
                if (($keep->SoDienThoai === null || $keep->SoDienThoai === '') && ($source->SoDienThoai ?? '') !== '') {
                    $updates['SoDienThoai'] = $source->SoDienThoai;
                }
                if (($keep->GhiChu === null || $keep->GhiChu === '') && ($source->GhiChu ?? '') !== '') {
                    $updates['GhiChu'] = $source->GhiChu;
                }
                if ($updates !== []) {
                    $keep->fill($updates);
                }
            }

            if ($keep->isDirty()) {
                $keep->NgayCapNhat = now();
                $keep->save();
            }

            static::query()->whereIn('Id', $mergeIds)->delete();
        });

        return [
            'ok' => true,
            'keep_id' => $keepId,
            'merged_count' => count($mergeIds),
            'phan_cong_updated' => $phanCongUpdated,
        ];
    }
}
