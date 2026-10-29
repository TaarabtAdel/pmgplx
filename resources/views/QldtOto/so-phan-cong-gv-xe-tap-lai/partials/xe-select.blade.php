@php
    $selected = trim((string) ($selectedBienSo ?? ''));
    $name = (string) ($name ?? 'bien_so');
@endphp
<select class="form-control form-control-sm sel-bien-so chon-s2" name="{{ $name }}" data-placeholder="— Không chọn —">
    <option value="">— Không chọn —</option>
    @foreach ($xeTapOptions as $opt)
        <option value="{{ $opt['bien_so'] }}" @selected($selected === (string) ($opt['bien_so'] ?? ''))>
            {{ $opt['label'] }}
        </option>
    @endforeach
</select>
