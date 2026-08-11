@php
    $value = old($name, $value ?? '07:00');
    $parts = explode(':', substr((string) $value, 0, 5));
    $hour = isset($parts[0]) ? (int) $parts[0] : 7;
    $minute = isset($parts[1]) ? (int) $parts[1] : 0;
    if ($minute < 0) {
        $minute = 0;
    }
    if ($minute > 59) {
        $minute = 59;
    }
    $id = $name;
@endphp

<div class="time-picker" data-time-picker="{{ $id }}">
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ sprintf('%02d:%02d', $hour, $minute) }}">
    <select class="form-control form-control-sm time-hour" aria-label="Giờ">
        @for ($h = 0; $h <= 23; $h++)
            <option value="{{ sprintf('%02d', $h) }}" @selected($h === $hour)>{{ sprintf('%02d', $h) }}</option>
        @endfor
    </select>
    <span>:</span>
    <select class="form-control form-control-sm time-minute" aria-label="Phút">
        @for ($m = 0; $m <= 59; $m++)
            <option value="{{ sprintf('%02d', $m) }}" @selected($m === $minute)>{{ sprintf('%02d', $m) }}</option>
        @endfor
    </select>
</div>
