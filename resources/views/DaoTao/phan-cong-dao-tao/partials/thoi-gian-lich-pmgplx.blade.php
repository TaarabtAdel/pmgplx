@php
    /** @var \Carbon\Carbon|null $tu */
    /** @var \Carbon\Carbon|null $den */
    $mode = $mode ?? 'range';
@endphp
@if ($mode === 'start')
    {{ ($tu ?? null) ? $tu->format('d/m/Y H:i') : '—' }}
@elseif ($mode === 'end')
    {{ ($den ?? null) ? $den->format('d/m/Y H:i') : '—' }}
@elseif (($tu ?? null) && ($den ?? null))
    @if ($tu->isSameDay($den))
        {{ $tu->format('d/m/Y') }} {{ $tu->format('H:i') }}–{{ $den->format('H:i') }}
    @else
        {{ $tu->format('d/m/Y H:i') }} – {{ $den->format('d/m/Y H:i') }}
    @endif
@elseif ($tu ?? null)
    {{ $tu->format('d/m/Y H:i') }}
@else
    —
@endif
