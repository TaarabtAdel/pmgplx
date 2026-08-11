@php
    $timeSlots = $timeSlots ?? \App\Models\PMGPLX\KhoaHoc::$TIME_SLOTS;
    $gioBD = $gioBD ?? '07:00';
    $gioKT = $gioKT ?? '11:00';
    $selectedSlot = '';
    foreach ($timeSlots as $i => $slot) {
        if ($slot['start'] === $gioBD && $slot['end'] === $gioKT) {
            $selectedSlot = (string) $i;
            break;
        }
    }
@endphp

<div class="form-group {{ $colClass ?? 'col-md-2' }}">
    <label for="khung_gio">Khung giờ</label>
    <select id="khung_gio" class="form-control form-control-sm" data-khung-gio>
        <option value="">-- Chọn khung giờ --</option>
        @foreach ($timeSlots as $i => $slot)
            <option
                value="{{ $i }}"
                data-start="{{ $slot['start'] }}"
                data-end="{{ $slot['end'] }}"
                @selected($selectedSlot === (string) $i)
            >
                {{ $slot['start'] }} → {{ $slot['end'] }}
            </option>
        @endforeach
    </select>
</div>
