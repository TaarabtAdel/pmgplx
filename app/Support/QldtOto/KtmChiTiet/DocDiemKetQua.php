<?php

namespace App\Support\QldtOto\KtmChiTiet;

use PhpOffice\PhpSpreadsheet\IOFactory;

/** File điểm LT / MP / CC — khóa SBD hoặc CCCD. */
final class DocDiemKetQua
{
    /** Thi lại LT: chỉ dòng có giá trị cột «Kết quả». */
    public const CHE_DO_THI_LAI_LT = 'thi_lai_lt';

    /** Thi lại MP: chỉ dòng có «Kết quả» = Đạt (Đ). */
    public const CHE_DO_THI_LAI_MP_DAT = 'thi_lai_mp_dat';

    /** Thi lại CC: chỉ dòng có số điểm ở cột «Điểm CC» (không cập nhật dòng trống). */
    public const CHE_DO_THI_LAI_CC = 'thi_lai_cc';

    /**
     * @param  self::CHE_DO_THI_LAI_*|null  $cheDoThiLai  Chỉ dùng khi báo cáo chi tiết là thi lại.
     * @return array<string, string> sbd hoặc cccd => Đ|KĐ
     */
    public static function docTheoSbd(string $path, ?string $cheDoThiLai = null): array
    {
        return self::doc($path, 'sbd', false, $cheDoThiLai);
    }

    /**
     * CC (chữ chi): cột tiêu đề chứa «Điểm CC»; điểm số ≥ 5 = Đ, &lt; 5 = KĐ; khớp CCCD / Họ tên + Ngày sinh.
     *
     * @param  self::CHE_DO_THI_LAI_CC|null  $cheDoThiLai
     * @return array<string, string>
     */
    public static function docChuChi(string $path, ?string $cheDoThiLai = null): array
    {
        return self::doc($path, 'cccd', true, $cheDoThiLai);
    }

    /**
     * @return array<string, string>
     */
    private static function doc(string $path, string $khoa, bool $chuChi, ?string $cheDoThiLai = null): array
    {
        $spreadsheet = IOFactory::load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = DocSheet::timHangTieuDe(
                $sheet,
                $chuChi
                    ? ['điểm cc', 'diem cc', 'cccd', 'kết quả']
                    : ['kết quả', 'ket qua', 'sbd', 'số báo danh', 'cccd'],
                40,
            );
            if ($headerRow === null) {
                throw new \InvalidArgumentException('Không đọc được tiêu đề file điểm: '.basename($path));
            }
            $cols = DocSheet::mapCotTuHang($sheet, $headerRow, 30);
            $colKq = $chuChi
                ? (self::timCot($cols, ['điểm cc', 'diem cc']) ?? self::timCot($cols, ['kết quả', 'ket qua']))
                : self::timCot($cols, ['kết quả', 'ket qua']);
            $colDung = $chuChi ? null : self::timCot($cols, ['trả lời đúng', 'tra loi dung', 'trả lời đung']);
            $colSai = $chuChi ? null : self::timCot($cols, ['trả lời sai', 'tra loi sai']);
            if ($chuChi && $colKq === null) {
                throw new \InvalidArgumentException('File CC thiếu cột ĐIỂM CC (hoặc Kết quả): '.basename($path));
            }
            if (! $chuChi && $colKq === null && $colDung === null) {
                throw new \InvalidArgumentException('File thiếu cột Kết quả hoặc Trả lời đúng: '.basename($path));
            }
            $colSbd = self::timCot($cols, ['sbd', 'số báo danh', 'so bao danh']);
            $colCccd = self::timCot($cols, ['cccd', 'số cccd', 'số cmt', 'cmt', 'số định danh', 'số cmt/cccd']);
            $colHoTen = self::timCot($cols, ['họ và tên', 'họ tên', 'họ va tên']);
            $colNs = self::timCot($cols, ['ngày sinh', 'ngay sinh']);
            $colHang = self::timCot($cols, ['hạng', 'hang gplx', 'hạng gplx']);

            $out = [];
            for ($r = $headerRow + 1; $r <= $headerRow + 500; $r++) {
                $kq = $chuChi
                    ? self::ketQuaChuChiTuHang($sheet, $r, $colKq, $cheDoThiLai)
                    : self::ketQuaLtTuHang($sheet, $r, $colKq, $colDung, $colSai, $cheDoThiLai);
                if ($kq === '') {
                    continue;
                }
                $keySbd = $colSbd !== null ? DocSheet::normalizeSbd(self::at($sheet, $r, $colSbd)) : '';
                $keyCccd = $colCccd !== null ? DocSheet::normalizeCccd(self::at($sheet, $r, $colCccd)) : '';
                if ($keyCccd === '' && ! $chuChi) {
                    $cDau = $colCccd ?? $colSbd ?? 1;
                    $cCuoi = max($cDau, $colHang ?? $cDau, ($colNs ?? $cDau) + 3);
                    $keyCccd = DocSheet::timCccdTrongHang($sheet, $r, $cDau, min($cCuoi + 2, $cDau + 8));
                }
                $hoTen = $colHoTen !== null ? mb_strtoupper(trim(self::at($sheet, $r, $colHoTen))) : '';
                $ngaySinh = $colNs !== null ? self::at($sheet, $r, $colNs) : '';
                $kTenNs = DocSheet::khoaHoTenNgaySinh($hoTen, $ngaySinh);

                if ($khoa === 'cccd') {
                    if ($keyCccd === '' && $kTenNs === '') {
                        continue;
                    }
                    if ($keyCccd !== '') {
                        $out[$keyCccd] = $kq;
                    }
                    if ($kTenNs !== '') {
                        $out[$kTenNs] = $kq;
                    }

                    continue;
                }

                if ($keySbd === '' && $keyCccd === '') {
                    continue;
                }
                if ($keySbd !== '') {
                    $out[$keySbd] = $kq;
                }
                if ($keyCccd !== '') {
                    $out[$keyCccd] = $kq;
                }
                if ($kTenNs !== '') {
                    $out[$kTenNs] = $kq;
                }
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private static function ketQuaChuChiTuHang(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        ?int $colKq,
        ?string $cheDoThiLai,
    ): string {
        if ($colKq === null) {
            return '';
        }
        $raw = self::at($sheet, $row, $colKq);
        if ($cheDoThiLai === self::CHE_DO_THI_LAI_CC) {
            $s = str_replace(',', '.', trim($raw));
            if ($s === '' || ! is_numeric($s)) {
                return '';
            }
        }

        return DocSheet::ketQuaTuDiemCc($raw);
    }

    private static function ketQuaLtTuHang(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        ?int $colKq,
        ?int $colDung,
        ?int $colSai,
        ?string $cheDoThiLai = null,
    ): string {
        if ($cheDoThiLai === self::CHE_DO_THI_LAI_MP_DAT) {
            if ($colKq === null) {
                return '';
            }
            $tuKq = DocSheet::chuanHoaKetQua(self::at($sheet, $row, $colKq));

            return $tuKq === 'Đ' ? 'Đ' : '';
        }

        if ($colKq !== null) {
            $tuKq = DocSheet::chuanHoaKetQua(self::at($sheet, $row, $colKq));
            if ($tuKq !== '') {
                return $tuKq;
            }
        }

        if ($cheDoThiLai === self::CHE_DO_THI_LAI_LT) {
            return '';
        }

        $soDung = $colDung !== null ? self::at($sheet, $row, $colDung) : '';
        $kq = DocSheet::ketQuaTuSoCauDungLt($soDung);
        if ($kq !== '') {
            return $kq;
        }

        // Một số file thi lại: cột «Kết quả» = 0, số câu đúng nằm lệch sang cột «Trả lời sai»
        $rawKq = $colKq !== null ? trim(self::at($sheet, $row, $colKq)) : '';
        if ($colSai !== null && ($soDung === '' || $soDung === '0') && ($rawKq === '' || $rawKq === '0')) {
            $kq = DocSheet::ketQuaTuSoCauDungLt(self::at($sheet, $row, $colSai));
            if ($kq !== '') {
                return $kq;
            }
        }

        return '';
    }

    /** @param  array<string, int>  $cols */
    private static function timCot(array $cols, array $need): ?int
    {
        foreach ($need as $n) {
            foreach ($cols as $label => $idx) {
                if (str_contains($label, $n)) {
                    return $idx;
                }
            }
        }

        return null;
    }

    private static function at(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, ?int $col): string
    {
        if ($col === null) {
            return '';
        }
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return DocSheet::cellStr($sheet, $letter.$row);
    }
}