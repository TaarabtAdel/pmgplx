@php
    $active = (bool) ($active ?? false);
@endphp
@if ($active)
    <span class="text-success font-weight-bold" title="Hiệu lực">✓</span>
@else
    <span class="text-danger font-weight-bold" title="Không hiệu lực">⛔</span>
@endif
