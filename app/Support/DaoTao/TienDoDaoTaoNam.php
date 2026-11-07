<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\TienDoDaoTao;
use App\Models\DaoTao\TienDoDuKienLoaiKhoa;
use App\Models\DaoTao\TienDoDuKienNgayNghi;
use App\Models\DaoTao\TienDoThucTeLoaiKhoa;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLXOLD\KhoaHoc as PmgplxBanCuKhoaHoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tiến độ đào tạo trong năm: khoá thực tế (PMGPLX KhoaHoc) + khoá dự kiến nối tiếp theo từng loại khoá.
 *
 * Khoá thực tế: các mã cùng hạng, cùng khai giảng và bế giảng (vd. B01K33 + B01K34) gộp thành một đợt.
 *
 * Khoá dự kiến: thực hành = tốt nghiệp khoá trước + SoNgayCach; khai giảng = thực hành − SoNgayLyThuyet (lý thuyết online,
 * dương lịch; mỗi đợt Tết Nguyên Đán trong khoảng cộng thêm 3 ngày); tốt nghiệp = thực hành + SoNgayThucHanh
 * (ngày học, bỏ ngày nghỉ lễ cấu hình).
 */
final class TienDoDaoTaoNam
{
    /** Thứ tự hiển thị hạng. */
    public const HANG = ['B01', 'C', 'B'];

    public const NHAN_HANG = [
        'B01' => 'Số tự động',
        'C' => 'C1',
        'B' => 'Số sàn',
    ];

    /** Loại khoá của khoá thực tế chưa gán riêng (TienDoThucTeLoaiKhoa). */
    public const LOAI_KHOA_THEO_HANG = [
        'B01' => 'SỐ TỰ ĐỘNG',
        'C' => 'C1',
        'B' => 'SỐ SÀN',
    ];

    /** Tiền tố tên khoá theo hạng: B01-K39, C1-K07, B-K56. */
    private const TIEN_TO = ['B01' => 'B01', 'C' => 'C1', 'B' => 'B'];

    private const GIAO_VIEN_MOI_XE_THUC_TE = 2;

    /** Ước tính ngày thực hành cho khoá PMGPLX chưa có lịch xe. */
    private const LY_THUYET_MAC_DINH = ['B01' => 17, 'C' => 19, 'B' => 19];

    private const TOI_DA_KHOA_MOI_CHUOI = 60;

    /**
     * Khoá phần mềm bản cũ đưa vào tiến độ: khai giảng trong [từ, trước) — các khoá năm 2026 mở trên bản cũ
     * trước khi chuyển sang bản mới (từ tháng 4/2026 khoá mở trên bản mới).
     */
    private const BAN_CU_KHAI_GIANG = ['2026-01-01', '2026-04-01'];

    /** @var list<string> */
    private array $canhBaoNguon = [];

    /** @var array<string, true> Y-m-d */
    private array $ngayNghi = [];

    /** @var list<array{ten: string, tu: Carbon, den: Carbon}> */
    private array $dotNghi = [];

    public function __construct(private readonly int $nam) {}

    /**
     * @return array{
     *     khoa: list<array<string, mixed>>,
     *     tat_ca: list<array<string, mixed>>,
     *     canh_bao: list<string>,
     *     tong_hop: list<array<string, mixed>>
     * }
     */
    public function lap(): array
    {
        $this->napNgayNghi();

        $thucTe = $this->khoaThucTe();
        [$duKien, $canhBao] = $this->khoaDuKien($thucTe);

        $tatCa = array_map(function (array $khoa): array {
            $khoa['luu_y'] = $this->luuY($khoa);

            return $khoa;
        }, [...$thucTe, ...$duKien]);

        usort($tatCa, fn (array $a, array $b): int => [$a['khai_giang'], array_search($a['hang'], self::HANG, true)]
            <=> [$b['khai_giang'], array_search($b['hang'], self::HANG, true)]);

        $trongNam = array_values(array_filter($tatCa, fn (array $k): bool => $k['khai_giang']->year === $this->nam));

        return [
            'khoa' => $trongNam,
            'tat_ca' => $tatCa,
            'canh_bao' => [...$this->canhBaoNguon, ...$canhBao],
            'tong_hop' => self::tongHop($trongNam),
        ];
    }

    /**
     * Học viên đang học tại một ngày (khai giảng ≤ ngày ≤ tốt nghiệp).
     *
     * @param  list<array<string, mixed>>  $tatCa
     * @return array{tong: int, thuc_te: int, du_kien: int, theo_hang: array<string, int>, so_khoa: int}
     */
    public static function dangHocTaiNgay(array $tatCa, Carbon $ngay): array
    {
        $dangHoc = array_filter($tatCa, fn (array $k): bool => $k['khai_giang']->lte($ngay) && $k['tot_nghiep']->gte($ngay));

        $theoHang = array_fill_keys(self::HANG, 0);
        $thucTe = 0;
        $duKien = 0;
        foreach ($dangHoc as $k) {
            $theoHang[$k['hang']] += $k['so_hoc_vien'];
            if ($k['nguon'] === 'thuc_te') {
                $thucTe += $k['so_hoc_vien'];
            } else {
                $duKien += $k['so_hoc_vien'];
            }
        }

        return [
            'tong' => $thucTe + $duKien,
            'thuc_te' => $thucTe,
            'du_kien' => $duKien,
            'theo_hang' => $theoHang,
            'so_khoa' => count($dangHoc),
        ];
    }

    private function napNgayNghi(): void
    {
        foreach (TienDoDuKienNgayNghi::query()->orderBy('TuNgay')->get() as $dot) {
            $tu = $dot->TuNgay->copy()->startOfDay();
            $den = $dot->DenNgay->copy()->startOfDay();
            if ($den->lt($tu)) {
                [$tu, $den] = [$den, $tu];
            }
            $this->dotNghi[] = ['ten' => (string) $dot->TenNgayNghi, 'tu' => $tu, 'den' => $den];
            for ($d = $tu->copy(); $d->lte($den); $d->addDay()) {
                $this->ngayNghi[$d->toDateString()] = true;
            }
        }
    }

    private function laNgayNghi(Carbon $ngay): bool
    {
        return isset($this->ngayNghi[$ngay->toDateString()]);
    }

    /** Cộng n ngày học, bỏ qua ngày nghỉ. */
    private function congNgayHoc(Carbon $tu, int $soNgay): Carbon
    {
        $d = $tu->copy();
        while ($soNgay > 0) {
            $d->addDay();
            if (! $this->laNgayNghi($d)) {
                $soNgay--;
            }
        }

        return $d;
    }

    /** Lùi n ngày học, bỏ qua ngày nghỉ (dùng cho thực hành → tốt nghiệp). */
    private function truNgayHoc(Carbon $tu, int $soNgay): Carbon
    {
        $d = $tu->copy();
        while ($soNgay > 0) {
            $d->subDay();
            if (! $this->laNgayNghi($d)) {
                $soNgay--;
            }
        }

        return $d;
    }

    /** Lý thuyết online: thực hành − n ngày dương lịch; mỗi đợt Tết âm lịch giao khoảng thêm 3 ngày. */
    private function khaiGiangTuThucHanh(Carbon $thucHanh, int $soNgayLyThuyet): Carbon
    {
        $d = $thucHanh->copy()->subDays($soNgayLyThuyet);
        $d->subDays($this->soNgayThemLyThuyetViTetAmLich($d->copy(), $thucHanh));

        return $d->startOfDay();
    }

    /** Có ngày khai giảng cố định → ngày thực hành (cùng quy tắc lý thuyết online). */
    private function thucHanhTuKhaiGiang(Carbon $khaiGiang, int $soNgayLyThuyet): Carbon
    {
        $d = $khaiGiang->copy()->addDays($soNgayLyThuyet);
        $d->addDays($this->soNgayThemLyThuyetViTetAmLich($khaiGiang, $d->copy()));

        return $d->startOfDay();
    }

    private function laDotTetAmLich(string $tenDot): bool
    {
        $t = mb_strtoupper(trim($tenDot));

        return str_contains($t, 'TẾT NGUYÊN ĐÁN');
    }

    /** Số ngày dương lịch thêm vào giai đoạn lý thuyết khi giao với Tết Nguyên Đán (mỗi đợt +3). */
    private function soNgayThemLyThuyetViTetAmLich(Carbon $tu, Carbon $den): int
    {
        if ($tu->gt($den)) {
            [$tu, $den] = [$den, $tu];
        }

        $them = 0;
        foreach ($this->dotNghi as $dot) {
            if (! $this->laDotTetAmLich($dot['ten'])) {
                continue;
            }
            if ($dot['tu']->lte($den) && $dot['den']->gte($tu)) {
                $them += 3;
            }
        }

        return $them;
    }

    /** Tốt nghiệp khoá trước + SoNgayCach (ngày dương lịch; ngày nghỉ lễ không đẩy lùi khoảng cách). */
    private function thucHanhSau(Carbon $totNghiepTruoc, int $soNgayCach): Carbon
    {
        return $totNghiepTruoc->copy()->addDays($soNgayCach);
    }

    /**
     * Gom tên loại khoá tương đương để nối tiếp đúng chuỗi (thực tế vs cấu hình dự kiến).
     */
    private function chuanHoaLoaiKhoa(string $ten): string
    {
        $s = mb_strtoupper(trim($ten));
        $s = preg_replace('/[\s\-–—]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        if (preg_match('/SỐ\s*TỰ\s*ĐỘNG/u', $s)) {
            if (preg_match('/ĐÊM/u', $s)) {
                return 'SỐ TỰ ĐỘNG ĐÊM';
            }
            if (preg_match('/\b25\b/u', $s)) {
                return 'SỐ TỰ ĐỘNG 25';
            }

            return 'SỐ TỰ ĐỘNG';
        }

        if (preg_match('/SỐ\s*SÀN/u', $s)) {
            if (preg_match('/\b60\b/u', $s)) {
                return 'SỐ SÀN 60';
            }

            // «SỐ SÀN» (mặc định PMGPLX) và «SỐ SÀN 100» — cùng chuỗi khoá full.
            return 'SỐ SÀN 100';
        }

        if (preg_match('/\bC1\b/u', $s) || preg_match('/\bHẠNG\s*C\b/u', $s)) {
            return 'C1';
        }

        return $s;
    }

    /** @return list<array<string, mixed>> */
    private function khoaThucTe(): array
    {
        $loaiKhoaGan = TienDoThucTeLoaiKhoa::query()->pluck('TenLoaiKhoa', 'MaKhoaLop');

        return collect($this->lopPmgplx())
            ->map(fn (array $l): array => [
                ...$l,
                'loai_khoa' => (string) ($loaiKhoaGan[$l['ma']] ?? self::LOAI_KHOA_THEO_HANG[$l['hang']]),
                'gan_rieng' => isset($loaiKhoaGan[$l['ma']]),
            ])
            ->groupBy(fn (array $l) => $l['hang'].'|'.$l['loai_khoa'].'|'.$l['khai_giang']->toDateString().'|'.$l['tot_nghiep']->toDateString())
            ->map(function ($dot): array {
                $dot = $dot->sortBy(fn (array $l) => self::soThuTuKhoa($l['ma']) ?? PHP_INT_MAX)->values();
                $dau = $dot->first();
                $soGiaoVien = (int) $dot->sum('so_giao_vien');

                return [
                    'nguon' => 'thuc_te',
                    'ten' => self::tenDot($dot->pluck('ma')->all()),
                    'ma_khoa_lop' => $dot->pluck('ma')->all(),
                    'hang' => $dau['hang'],
                    'loai_khoa' => $dau['loai_khoa'],
                    'so_hoc_vien' => (int) $dot->sum('so_hoc_vien'),
                    'so_giao_vien' => $soGiaoVien ?: null,
                    'so_xe' => $soGiaoVien ? (int) ceil($soGiaoVien / self::GIAO_VIEN_MOI_XE_THUC_TE) : null,
                    'so_xe_tang' => 0,
                    'khai_giang' => $dau['khai_giang'],
                    'thuc_hanh' => $dot->pluck('thuc_hanh')->filter()->min(),
                    'thuc_hanh_uoc_tinh' => $dot->every(fn (array $l) => $l['thuc_hanh_uoc_tinh']),
                    'tot_nghiep' => $dau['tot_nghiep'],
                    'ban_cu' => $dot->contains(fn (array $l) => $l['ban_cu']),
                    'loai_khoa_rieng' => $dot->contains(fn (array $l) => $l['gan_rieng']),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Khoá PMGPLX bản mới, bổ sung khoá phần mềm bản cũ khai giảng trong BAN_CU_KHAI_GIANG (chưa chuyển sang
     * bản mới). Trùng mã khoá-lớp thì lấy bản mới.
     *
     * @return array<string, array<string, mixed>> mã khoá-lớp (vd. B01K39) => khoá
     */
    private function lopPmgplx(): array
    {
        $out = $this->lopBanMoi();
        foreach ($this->lopBanCu() as $ma => $lop) {
            $out[$ma] ??= $lop;
        }

        return $out;
    }

    /**
     * Thực hành = ngày xe tập sớm nhất (KhoaHoc_XeTap); chưa có lịch xe thì ước tính khai giảng + số ngày lý thuyết.
     *
     * @return array<string, array<string, mixed>>
     */
    private function lopBanMoi(): array
    {
        $xeTap = fn () => DB::connection('sqlsrv')
            ->table('KhoaHoc_XeTap')
            ->whereColumn('KhoaHoc_XeTap.MaKH', 'KhoaHoc.MaKH')
            ->where('KhoaHoc_XeTap.IsKhoaHocXeTap', 0);

        $rows = KhoaHoc::query()
            ->select(['KhoaHoc.MaKH', 'KhoaHoc.TenKH', 'KhoaHoc.NgayKG', 'KhoaHoc.NgayBG', 'KhoaHoc.TongSoHV'])
            ->selectSub($xeTap()->selectRaw('MIN(KhoaHoc_XeTap.NgayBD)'), 'ngay_thuc_hanh')
            ->selectSub($xeTap()->selectRaw('COUNT(DISTINCT KhoaHoc_XeTap.MaGV)'), 'so_giao_vien')
            ->selectSub(
                DB::connection('sqlsrv')->table('NguoiLX_HoSo')->selectRaw('COUNT(*)')->whereColumn('NguoiLX_HoSo.MaKhoaHoc', 'KhoaHoc.MaKH'),
                'so_ho_so'
            )
            ->whereNotNull('KhoaHoc.NgayKG')
            ->whereNotNull('KhoaHoc.NgayBG')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $lop = $this->lopTuDong($row, $row->ngay_thuc_hanh, (int) $row->so_giao_vien, false);
            if ($lop !== null) {
                $out[$lop['ma']] = $lop;
            }
        }

        return $out;
    }

    /**
     * Bản cũ không có lịch xe (KhoaHoc_XeTap) nên thực hành luôn ước tính, không có số GV / xe.
     *
     * @return array<string, array<string, mixed>>
     */
    private function lopBanCu(): array
    {
        try {
            $rows = PmgplxBanCuKhoaHoc::query()
                ->select(['KhoaHoc.MaKH', 'KhoaHoc.TenKH', 'KhoaHoc.NgayKG', 'KhoaHoc.NgayBG', 'KhoaHoc.TongSoHV'])
                ->selectSub(
                    DB::connection('sqlsrv_old')->table('NguoiLX_HoSo')->selectRaw('COUNT(*)')->whereColumn('NguoiLX_HoSo.MaKhoaHoc', 'KhoaHoc.MaKH'),
                    'so_ho_so'
                )
                ->where('KhoaHoc.NgayKG', '>=', self::BAN_CU_KHAI_GIANG[0])
                ->where('KhoaHoc.NgayKG', '<', self::BAN_CU_KHAI_GIANG[1])
                ->whereNotNull('KhoaHoc.NgayBG')
                ->get();
        } catch (\Throwable $e) {
            $this->canhBaoNguon[] = 'Không đọc được khoá phần mềm bản cũ ('.config('database.connections.sqlsrv_old.database').'): '.$e->getMessage();

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $lop = $this->lopTuDong($row, null, 0, true);
            if ($lop !== null) {
                $out[$lop['ma']] = $lop;
            }
        }

        return $out;
    }

    /**
     * Mã khoá-lớp lấy từ đuôi tên khoá: «45003K25B01K31» → B01K31, «4500K26BK051» → BK51.
     *
     * @return array<string, mixed>|null
     */
    private function lopTuDong(object $row, mixed $ngayThucHanh, int $soGiaoVien, bool $banCu): ?array
    {
        if (! preg_match('/(B01|C1|B)K(\d+)\s*$/i', (string) $row->TenKH, $m)) {
            return null;
        }
        $ma = strtoupper($m[1]).'K'.(int) $m[2];
        $hang = TienDoDaoTao::parseHangFromMaKhoaLop($ma);
        if ($hang === null) {
            return null;
        }

        $khaiGiang = Carbon::parse($row->NgayKG)->startOfDay();
        $thucHanh = $ngayThucHanh ? Carbon::parse($ngayThucHanh)->startOfDay() : null;

        return [
            'ma' => $ma,
            'hang' => $hang,
            'khai_giang' => $khaiGiang,
            'thuc_hanh' => $thucHanh ?? $this->congNgayHoc($khaiGiang, self::LY_THUYET_MAC_DINH[$hang]),
            'thuc_hanh_uoc_tinh' => $thucHanh === null,
            'tot_nghiep' => Carbon::parse($row->NgayBG)->startOfDay(),
            'so_hoc_vien' => (int) ($row->TongSoHV ?: $row->so_ho_so),
            'so_giao_vien' => $soGiaoVien,
            'ban_cu' => $banCu,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $thucTe
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    private function khoaDuKien(array $thucTe): array
    {
        $cuoiNam = Carbon::create($this->nam, 12, 31)->startOfDay();
        $canhBao = [];

        $totNghiepMuonNhat = [];
        $totNghiepMuonNhatTheoLoai = [];
        foreach ($thucTe as $k) {
            $hang = $k['hang'];
            if (! isset($totNghiepMuonNhat[$hang]) || $k['tot_nghiep']->gt($totNghiepMuonNhat[$hang])) {
                $totNghiepMuonNhat[$hang] = $k['tot_nghiep'];
            }
            $loaiKey = $hang.'|'.$this->chuanHoaLoaiKhoa($k['loai_khoa']);
            if (! isset($totNghiepMuonNhatTheoLoai[$loaiKey]) || $k['tot_nghiep']->gt($totNghiepMuonNhatTheoLoai[$loaiKey])) {
                $totNghiepMuonNhatTheoLoai[$loaiKey] = $k['tot_nghiep'];
            }
        }

        $duKien = [];
        $loaiKhoa = TienDoDuKienLoaiKhoa::query()
            ->with('tangXe')
            ->where('DangSuDung', true)
            ->orderBy('ThuTu')
            ->orderBy('Id')
            ->get();

        foreach ($loaiKhoa as $loai) {
            $hang = (string) $loai->Hang;
            if (! in_array($hang, self::HANG, true)) {
                continue;
            }

            // Nối tiếp khoá thực tế cùng loại (chuẩn hoá tên, vd. «SỐ TỰ ĐỘNG - 25 người» ↔ «SỐ TỰ ĐỘNG 25»).
            // Chỉ loại khoá mặc định của hạng mới dùng tốt nghiệp muộn nhất cả hạng khi chưa có khoá cùng loại.
            $loaiChuan = $this->chuanHoaLoaiKhoa((string) $loai->TenLoai);
            $noiTiep = $totNghiepMuonNhatTheoLoai[$hang.'|'.$loaiChuan] ?? null;
            if ($noiTiep === null && $loaiChuan === $this->chuanHoaLoaiKhoa(self::LOAI_KHOA_THEO_HANG[$hang] ?? '')) {
                $noiTiep = $totNghiepMuonNhat[$hang] ?? null;
            }
            if ($loai->NgayKhaiGiang !== null) {
                $khaiGiang = $loai->NgayKhaiGiang->copy()->startOfDay();
                $thucHanh = $this->thucHanhTuKhaiGiang($khaiGiang, $loai->SoNgayLyThuyet);
            } elseif ($noiTiep !== null) {
                $thucHanh = $this->thucHanhSau($noiTiep, $loai->SoNgayCach);
                $khaiGiang = $this->khaiGiangTuThucHanh($thucHanh, $loai->SoNgayLyThuyet);
            } else {
                $canhBao[] = "«{$loai->TenLoai}»: chưa có khoá thực tế ".self::NHAN_HANG[$hang].' để nối tiếp — hãy nhập ngày khai giảng cho loại khoá này.';

                continue;
            }

            $gvCoBan = $loai->soGvCoBan();
            for ($i = 0; $i < self::TOI_DA_KHOA_MOI_CHUOI && $khaiGiang->lte($cuoiNam); $i++) {
                $totNghiep = $this->congNgayHoc($thucHanh, $loai->SoNgayThucHanh);
                $tangGv = (int) $loai->tangXe->filter(fn ($t) => $t->TuNgay->lte($khaiGiang))->sum('SoXe');
                $soGv = max(0, $gvCoBan + $tangGv);

                $tenLoai = (string) $loai->TenLoai;
                $duKien[] = [
                    'nguon' => 'du_kien',
                    'ten' => 'Dự kiến '.$tenLoai.($i > 0 ? ' ('.($i + 1).')' : ''),
                    'ma_khoa_lop' => [],
                    'hang' => $hang,
                    'loai_khoa' => (string) $loai->TenLoai,
                    'mau_loai_khoa' => $loai->MauSac,
                    'so_hoc_vien' => max(0, $loai->SoHocVienToiDa + $tangGv * $loai->SoHocVienMoiXe),
                    'so_giao_vien' => $soGv * max(1, (int) $loai->SoGiaoVienMoiXe),
                    'so_xe' => (int) ceil($soGv / self::GIAO_VIEN_MOI_XE_THUC_TE),
                    'so_xe_tang' => $tangGv,
                    'so_hoc_vien_moi_xe' => $loai->SoHocVienMoiXe,
                    'so_giao_vien_moi_xe' => $loai->SoGiaoVienMoiXe,
                    'khai_giang' => $khaiGiang,
                    'thuc_hanh' => $thucHanh,
                    'tot_nghiep' => $totNghiep,
                ];

                $thucHanh = $this->thucHanhSau($totNghiep, $loai->SoNgayCach);
                $khaiGiang = $this->khaiGiangTuThucHanh($thucHanh, $loai->SoNgayLyThuyet);
            }
        }

        usort($duKien, fn (array $a, array $b): int => $a['khai_giang'] <=> $b['khai_giang']);

        return [$duKien, $canhBao];
    }

    /** @return list<string> */
    private function luuY(array $khoa): array
    {
        $luuY = [];
        foreach ($this->dotNghi as $dot) {
            $tu = $dot['tu']->max($khoa['khai_giang']);
            $den = $dot['den']->min($khoa['tot_nghiep']);
            if ($tu->gt($den)) {
                continue;
            }

            $khoang = $dot['tu']->eq($dot['den'])
                ? 'ngày '.$dot['tu']->format('d/m/Y')
                : 'từ '.$dot['tu']->format('d/m/Y').' đến hết '.$dot['den']->format('d/m/Y');
            $text = $dot['ten'].' '.$khoang;
            if ($khoa['nguon'] === 'du_kien') {
                $text .= ' (+'.($tu->diffInDays($den) + 1).' ngày)';
            }
            $luuY[] = $text;
        }

        if ($khoa['so_xe_tang'] !== 0) {
            $tang = $khoa['so_xe_tang'];
            $gv = abs($tang);
            $luuY[] = ($tang > 0 ? 'Tăng ' : 'Giảm ').$gv.' GV ('.($tang > 0 ? '+' : '−').abs($tang * $khoa['so_hoc_vien_moi_xe']).' HV)';
        }

        return $luuY;
    }

    /**
     * @param  list<array<string, mixed>>  $khoa
     * @return list<array<string, mixed>>
     */
    private static function tongHop(array $khoa): array
    {
        $out = [];
        foreach (self::HANG as $hang) {
            $theoHang = array_filter($khoa, fn (array $k): bool => $k['hang'] === $hang);
            $thucTe = array_filter($theoHang, fn (array $k): bool => $k['nguon'] === 'thuc_te');
            $duKien = array_filter($theoHang, fn (array $k): bool => $k['nguon'] === 'du_kien');
            $out[] = [
                'hang' => $hang,
                'nhan' => self::NHAN_HANG[$hang],
                'so_khoa_thuc_te' => count($thucTe),
                'hoc_vien_thuc_te' => array_sum(array_column($thucTe, 'so_hoc_vien')),
                'so_khoa_du_kien' => count($duKien),
                'hoc_vien_du_kien' => array_sum(array_column($duKien, 'so_hoc_vien')),
            ];
        }

        return $out;
    }

    private static function soThuTuKhoa(string $ma): ?int
    {
        return preg_match('/K(\d+)$/i', $ma, $m) ? (int) $m[1] : null;
    }

    /** ['B01K33', 'B01K34'] → «B01-K33, K34». */
    private static function tenDot(array $ma): string
    {
        $ten = [];
        foreach ($ma as $i => $m) {
            if (! preg_match('/^(B01|C1|B)K(\d+)$/i', $m, $p)) {
                $ten[] = $m;
            } else {
                $ten[] = $i === 0 ? strtoupper($p[1]).'-K'.$p[2] : 'K'.$p[2];
            }
        }

        return implode(', ', $ten);
    }
}
