{{-- $items: các mục menu QLĐT (config/menu_qldt.php) --}}
<ul class="{{ ($cap ?? 0) === 0 ? 'list-unstyled mb-0' : 'mb-1' }}">
    @foreach ($items as $item)
        <li class="{{ ($cap ?? 0) === 0 ? 'mb-3' : '' }}">
            @if (! empty($item['children']))
                <span class="{{ ($cap ?? 0) === 0 ? 'h6 font-weight-bold text-primary' : 'font-weight-bold' }}">{{ $item['title'] }}</span>
                @if (! empty($item['mo_ta']))
                    <span class="small text-muted">— {{ $item['mo_ta'] }}</span>
                @endif
                @include('QldtOto.partials.cay-menu', ['items' => $item['children'], 'cap' => ($cap ?? 0) + 1])
            @else
                @php $url = \App\Support\MenuQldt::url($item); @endphp
                @if ($url !== '#')
                    <a href="{{ $url }}">{{ $item['title'] }}</a>
                @else
                    {{ $item['title'] }}
                @endif
                @if (! empty($item['mo_ta']))
                    <span class="small text-muted">— {{ $item['mo_ta'] }}</span>
                @endif
            @endif
        </li>
    @endforeach
</ul>
