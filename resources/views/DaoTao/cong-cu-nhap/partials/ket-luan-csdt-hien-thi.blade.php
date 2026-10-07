@php
    $ketLuanVal = $value ?? null;
    $ketLuanInt = ($ketLuanVal === null || $ketLuanVal === '') ? null : (int) $ketLuanVal;
@endphp
@if ($ketLuanInt === null)
    —
@elseif ($ketLuanInt === 1)
    <span class="badge badge-success">Đạt</span>
@else
    <span class="badge badge-danger">Không đạt</span>
@endif
