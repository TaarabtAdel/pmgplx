@foreach ($parts as $part)
    <span class="ky-hieu-badge {{ $part['css_class'] }}" title="{{ $part['label'] }}">{{ $part['token'] }}</span>@if (! $loop->last)<span class="text-muted mx-1"> </span>@endif
@endforeach
