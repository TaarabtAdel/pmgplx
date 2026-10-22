{{-- $items: list<array{title: string, url?: string, route?: string, active?: string, children?: list}> --}}
@foreach ($items as $item)
    @php $dangChon = \App\Support\MenuQldt::dangChon($item); @endphp
    @if (! empty($item['children']))
        <div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle {{ $dangChon ? 'active' : '' }}" href="#">{{ $item['title'] }}</a>
            <div class="dropdown-menu">
                @include('PMGPLX.layouts.partials.menu-con', ['items' => $item['children']])
            </div>
        </div>
    @else
        @php $menuUrl = \App\Support\MenuQldt::url($item); @endphp
        <a class="dropdown-item {{ $menuUrl === '#' ? 'menu-chua-co-trang' : '' }} {{ $dangChon ? 'active' : '' }}"
           href="{{ $menuUrl }}">
            {{ $item['title'] }}
        </a>
    @endif
@endforeach
