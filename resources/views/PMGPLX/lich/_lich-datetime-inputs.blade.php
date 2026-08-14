@once
@push('styles')
<style>
    .lich-datetime-fields {
        display: flex;
        gap: 0.25rem;
        min-width: 10.5rem;
    }
    .lich-datetime-fields .lich-dt-date {
        flex: 1.4 1 0;
        min-width: 0;
    }
    .lich-datetime-fields .lich-dt-time {
        flex: 0.8 1 0;
        min-width: 4.5rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        text-align: center;
    }
</style>
@endpush
@push('scripts')
<script>
    function syncLichDatetimeField($wrap) {
        var date = ($wrap.find('.lich-dt-date').val() || '').trim();
        var time = ($wrap.find('.lich-dt-time').val() || '').trim();
        var $hidden = $wrap.find('.lich-dt-hidden');

        if (date !== '' && /^([01][0-9]|2[0-3]):[0-5][0-9]$/.test(time)) {
            $hidden.val(date + ' ' + time + ':00');
        } else {
            $hidden.val('');
        }
    }

    $(document).on('change input', '.lich-dt-date, .lich-dt-time', function () {
        syncLichDatetimeField($(this).closest('.lich-datetime-fields'));
    });

    $(document).on('submit', 'form:has(.lich-datetime-fields)', function () {
        $('.lich-datetime-fields').each(function () {
            syncLichDatetimeField($(this));
        });
    });
</script>
@endpush
@endonce

@php
    $dt = \Carbon\Carbon::parse($value);
    $isRequired = ! empty($required);
@endphp
<div class="lich-datetime-fields">
    <input
        type="date"
        class="form-control form-control-sm lich-dt-date"
        value="{{ $dt->format('Y-m-d') }}"
        @if ($isRequired) required @endif
        aria-label="Ngày"
    >
    <input
        type="text"
        class="form-control form-control-sm lich-dt-time"
        value="{{ $dt->format('H:i') }}"
        pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$"
        placeholder="HH:mm"
        maxlength="5"
        inputmode="numeric"
        title="Giờ 24h, ví dụ 05:59 hoặc 13:59"
        @if ($isRequired) required @endif
        aria-label="Giờ"
    >
    <input
        type="hidden"
        name="{{ $name }}"
        class="lich-dt-hidden"
        value="{{ $dt->format('Y-m-d H:i:s') }}"
    >
</div>
