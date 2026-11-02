<?php

namespace App\Support\OCR;

/**
 * Tách trường thông tin từ văn bản OCR của CCCD / GPLX (chuyển từ quet-qr/lib/parse.js).
 * Nhãn so khớp trên chuỗi đã bỏ dấu + chữ thường, giữ nguyên số ký tự để cắt lại chuỗi gốc,
 * nên chịu được lỗi OCR thường gặp: «S6/No», «Qué quán», «He ten Full name»…
 * Hỗ trợ cả bố cục nhãn–giá trị cùng dòng / dòng dưới (CCCD) và bố cục cột nhãn rồi cột giá trị (GPLX mẫu cũ).
 */
final class GiayToParser
{
    private const DATE = '/\b(\d{1,2})\s*[\/.\-]\s*(\d{1,2})\s*[\/.\-]\s*(\d{4})\b/u';

    private const LABELS = [
        'so' => '/\b(?:so|s6|s0)\s*[\/|!,.]?\s*no\b[\s:.]*/',
        'hoTen' => '/\bh[oe]\s*(?:va)?\s*ten\W*(?:full\s*name)?[\s:.]*|full\s*name[\s:.]*/',
        'ngaySinh' => '/ngay\s*(?:,\s*thang\s*,\s*nam\s*)?sinh\W*(?:date\s*of\s*bi\w*)?[\s:.]*|date\s*of\s*birth[\s:.]*/',
        'gioiTinh' => '/gioi\s*tinh\W*(?:sex)?[\s:.]*/',
        'quocTich' => '/quoc\s*\W?\s*\w{0,4}\W*(?:na\w*ty)?[\s:.]*|nationality[\s:.]*/',
        'queQuan' => '/que\s*quan\W*(?:place\s*of\s*origin)?[\s:.]*|place\s*of\s*origin[\s:.]*/',
        'noiTru' => '/noi\s*(?:thuong\s*tru|cu\s*tru)\W*(?:place\s*of\s*residence|address)?[\s:.]*|place\s*of\s*residence[\s:.]*/',
        'hetHan' => '/(?:co\s*gia\s*tri\s*den|gia\s*tri\s*den|date\s*of\s*expiry|het\s*han)\W*(?:exp\w*)?[\s:.\-]*/',
        'hang' => '/^hang\b\W*(?:class)?[\s:.]*/',
        'trungTuyen' => '/ngay\s*tr[uo]ng\s*tuyen\W*(?:beginning\s*date)?[\s:.]*|beginning\s*date[\s:.]*/',
        // CCCD mặt sau: «Ngày, tháng, năm / Date, month, year: 10/07/2021», thẻ 2024: «Ngày cấp / Date of issue».
        'ngayCap' => '/ngay\s*,?\s*thang\s*,?\s*nam(?!\s*sinh)(?:\s*cap)?\W*(?:date\s*,?\s*month\s*,?\s*year)?(?:\s*of\s*issue)?[\s:.]*|ngay\s*cap\W*(?:date\s*of\s*issue)?[\s:.]*|date\s*of\s*issue[\s:.]*/',
    ];

    /** Các trường có thể nằm trong «cột giá trị» của bố cục 2 cột. */
    private const COLUMN_KEYS = ['hoTen', 'ngaySinh', 'quocTich', 'queQuan', 'noiTru'];

    /** Các trường địa chỉ có thể xuống dòng (chỉ nối khi nhãn và giá trị cùng dòng). */
    private const MULTILINE_KEYS = ['queQuan', 'noiTru'];

    /** Bỏ dấu + chữ thường, mỗi ký tự gốc ra đúng một ký tự (để cắt chuỗi gốc theo vị trí). */
    public static function fold(string $s): string
    {
        $out = '';
        foreach (mb_str_split($s) as $c) {
            if ($c === 'đ' || $c === 'Đ') {
                $out .= 'd';

                continue;
            }
            $base = preg_replace('/\p{Mn}+/u', '', (string) \Normalizer::normalize($c, \Normalizer::FORM_D));
            $out .= mb_strtolower(mb_substr($base !== '' ? $base : $c, 0, 1));
        }

        return $out;
    }

    public static function detectType(string $text): string
    {
        $f = self::fold($text);
        if (preg_match('/can\s*cuoc|citizen\s*identity|identity\s*card|chung\s*minh\s*nhan\s*dan/', $f)) {
            return 'cccd';
        }
        if (preg_match('/giay\s*phep\s*lai\s*xe|driver|motor\s*vehicles|tr[uo]ng\s*tuyen|lai\s*xe/', $f)) {
            return 'gplx';
        }

        return 'khac';
    }

    /**
     * @return array{type: string, fields: array<string, string>}
     */
    public static function parse(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text) ?: []), fn ($l) => $l !== ''));
        $folded = array_map([self::class, 'fold'], $lines);
        $type = self::detectType($text);
        $v = self::collect($lines, $folded);
        $fields = [];
        $set = function (string $label, ?string $value) use (&$fields): void {
            if ($value !== null && $value !== '') {
                $fields[$label] = $value;
            }
        };

        $so = null;
        if (isset($v['so']) && preg_match('/\d{9,12}/', $v['so'], $m)) {
            $so = $m[0];
        } elseif (preg_match('/\b\d{12}\b/', $text, $m)) {
            $so = $m[0];
        }
        $set('so', $so);

        $hoTen = isset($v['hoTen']) && ! preg_match(self::DATE, $v['hoTen']) ? $v['hoTen'] : null;
        $set('ho_ten', $hoTen !== null && preg_match('/[A-ZÀ-Ỹ]{2,}/u', $hoTen) ? mb_strtoupper($hoTen) : $hoTen);

        $ngaySinh = isset($v['ngaySinh']) && preg_match(self::DATE, $v['ngaySinh'], $m) ? self::fmtDate($m) : null;
        $set('ngay_sinh', $ngaySinh);

        $gt = self::fold($v['gioiTinh'] ?? '');
        $set('gioi_tinh', preg_match('/\bnu\b/', $gt) ? 'Nữ' : (preg_match('/\bnam\b/', $gt) ? 'Nam' : null));

        $qt = self::fold($v['quocTich'] ?? '').' '.self::fold($v['gioiTinh'] ?? '');
        $set('quoc_tich', preg_match('/viet\s*nam/', $qt) ? 'Việt Nam' : ($v['quocTich'] ?? null));

        $set('que_quan', $v['queQuan'] ?? null);
        $set('noi_thuong_tru', $v['noiTru'] ?? null);

        $hetHan = null;
        if (isset($v['hetHan'])) {
            if (preg_match(self::DATE, $v['hetHan'], $m)) {
                $hetHan = self::fmtDate($m);
            } elseif (preg_match('/khong\s*th[oa]i\s*\w*n/', self::fold($v['hetHan']))) {
                $hetHan = 'Không thời hạn';
            }
        }
        if ($hetHan === null && $type === 'cccd' && ! isset($v['ngayCap'])) {
            $dates = [];
            preg_match_all(self::DATE, $text, $all, PREG_SET_ORDER);
            foreach ($all as $m) {
                $d = self::fmtDate($m);
                if ($d !== $ngaySinh) {
                    $dates[] = $d;
                }
            }
            usort($dates, fn ($a, $b) => (int) substr($b, -4) <=> (int) substr($a, -4));
            $hetHan = $dates[0] ?? null;
        }
        $set('co_gia_tri_den', $hetHan);

        // CCCD mặt sau: nhãn «Ngày, tháng, năm» / «Ngày cấp» + ngày ở cùng dòng hoặc vài dòng dưới.
        if (isset($v['ngayCap']) && preg_match(self::DATE, $v['ngayCap'], $m)) {
            $set('ngay_cap', self::fmtDate($m));
        } else {
            foreach ($folded as $i => $f) {
                if (preg_match(self::LABELS['ngayCap'], $f)) {
                    $tail = implode(' ', array_slice($lines, $i, 3));
                    if (preg_match(self::DATE, $tail, $m)) {
                        $set('ngay_cap', self::fmtDate($m));
                    }
                    break;
                }
            }
        }

        // GPLX: «Quảng Trị, ngày/date 06 tháng/month 07 năm/year 2016».
        $capRe = '/ngay\D{0,12}(\d{1,2})\D{1,20}thang\D{0,12}(\d{1,2})\D{1,20}nam\D{0,8}(\d{4})/';
        foreach ($folded as $i => $f) {
            if (preg_match($capRe, $f, $m, PREG_OFFSET_CAPTURE)) {
                if (! isset($fields['ngay_cap'])) {
                    $set('ngay_cap', sprintf('%02d/%02d/%s', (int) $m[1][0], (int) $m[2][0], $m[3][0]));
                }
                $viTri = mb_strlen(substr($f, 0, $m[0][1]));
                $noiCap = rtrim(trim(mb_substr($lines[$i], 0, $viTri)), " ,.");
                if ($noiCap !== '' && mb_strlen($noiCap) <= 30) {
                    $set('noi_cap', $noiCap);
                }
                break;
            }
        }

        if ($type === 'gplx') {
            $hangF = preg_replace('/\b([a-f])[li|]\b/', '${1}1', self::fold($v['hang'] ?? '')) ?? '';
            if (preg_match('/\b(a[1-4]?|b[12e]?|c1?e?|d[12]?e?|e|f[b-e]?)\b/', $hangF, $m)) {
                $set('hang', mb_strtoupper($m[1]));
            }

            $ttIdx = null;
            foreach ($folded as $i => $f) {
                if (preg_match(self::LABELS['trungTuyen'], $f)) {
                    $ttIdx = $i;
                    break;
                }
            }
            if ($ttIdx !== null) {
                $tt = null;
                if (isset($v['trungTuyen']) && preg_match(self::DATE, $v['trungTuyen'], $m)) {
                    $tt = self::fmtDate($m);
                } elseif (preg_match(self::DATE, implode(' ', array_slice($lines, $ttIdx)), $m)) {
                    $tt = self::fmtDate($m);
                }
                $set('ngay_trung_tuyen', $tt);
            }
            if (preg_match('/\b[A-Z]{2}\s?\d{6,}\b/', $text, $m)) {
                $set('so_phoi', str_replace(' ', '', $m[0]));
            }
        }

        return ['type' => $type, 'fields' => $fields];
    }

    /**
     * Duyệt từng dòng, gán giá trị cho nhãn (cùng dòng, dòng dưới, hoặc theo hàng đợi cột).
     *
     * @param  list<string>  $lines
     * @param  list<string>  $folded
     * @return array<string, string>
     */
    private static function collect(array $lines, array $folded): array
    {
        $values = [];
        $queue = [];
        $continueKey = null;

        foreach ($lines as $i => $line) {
            $hit = self::matchLabel($folded[$i]);
            if ($hit !== null) {
                [$key, $start, $len] = $hit;
                $rest = trim(mb_substr($line, $start + $len));
                $continueKey = null;
                if (isset($values[$key])) {
                    continue;
                }
                if ($rest !== '') {
                    $values[$key] = $rest;
                    if (in_array($key, self::MULTILINE_KEYS, true)) {
                        $continueKey = $key;
                    }
                } elseif (! in_array($key, $queue, true)) {
                    $queue[] = $key;
                }

                continue;
            }
            if ($queue !== []) {
                $key = in_array($queue[0], self::COLUMN_KEYS, true) || count($queue) === 1 ? array_shift($queue) : null;
                if ($key !== null) {
                    $values[$key] = $line;
                }
                $continueKey = null;

                continue;
            }
            if ($continueKey !== null && ! preg_match(self::DATE, $line) && ! preg_match('/\bngay\b/', $folded[$i])) {
                $values[$continueKey] .= ', '.$line;
            }
            $continueKey = null;
        }

        foreach ($values as $k => $val) {
            $val = preg_replace('/\s+/u', ' ', $val) ?? $val;
            $values[$k] = trim(preg_replace('/[.,;:|]+$/u', '', $val) ?? $val);
        }

        return $values;
    }

    /** @return array{0: string, 1: int, 2: int}|null [key, vị trí ký tự, độ dài ký tự] — nhãn xuất hiện sớm nhất. */
    private static function matchLabel(string $folded): ?array
    {
        $best = null;
        foreach (self::LABELS as $key => $re) {
            if (preg_match($re, $folded, $m, PREG_OFFSET_CAPTURE)) {
                $pos = mb_strlen(substr($folded, 0, $m[0][1]));
                if ($best === null || $pos < $best[1]) {
                    $best = [$key, $pos, mb_strlen($m[0][0])];
                }
            }
        }

        return $best;
    }

    /** @param  array<int, string>  $m */
    private static function fmtDate(array $m): string
    {
        return sprintf('%02d/%02d/%s', (int) $m[1], (int) $m[2], $m[3]);
    }
}
