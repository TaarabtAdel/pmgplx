@php
    $selectedMa = (string) ($selectedMa ?? '');
    $selectedHoTen = trim((string) ($selectedHoTen ?? ''));
    if ($selectedMa === '' && $selectedHoTen !== '') {
        foreach ($giaoVienOptions as $opt) {
            if (($opt['ho_ten'] ?? '') === $selectedHoTen) {
                $selectedMa = (string) ($opt['ma'] ?? '');
                break;
            }
        }
    }
    $name = (string) ($name ?? 'ma_gv');
@endphp
<select class="form-control form-control-sm sel-gv chon-s2" name="{{ $name }}" data-placeholder="— Chọn giáo viên —">
    <option value="">— Chọn giáo viên —</option>
    @foreach ($giaoVienOptions as $opt)
        <option value="{{ $opt['ma'] }}"
                data-ghi-chu="{{ $opt['ghi_chu'] ?? '' }}"
                @selected($selectedMa === (string) ($opt['ma'] ?? ''))>
            {{ $opt['label'] }}
        </option>
    @endforeach
</select>
