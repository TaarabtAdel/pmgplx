{{--
    $items: list<array{title: string, url?: string, route?: string, active?: string, children?: list}>
    $khoaCha / $cap: khoá phân quyền của tab QLĐT (CayChucNang) — kiểm tra quyền ở Menu 1 (cap 1) và Menu 2 (cap 2);
    nhánh sâu hơn đi theo Menu 2. Không truyền $khoaCha thì không lọc.
--}}
@php
    $khoaCha = $khoaCha ?? null;
    $cap = $cap ?? 1;
@endphp
@foreach ($items as $item)
    @php
        $khoaMuc = $khoaCha !== null && $cap <= 2 ? $khoaCha.'/'.\App\Support\HeThong\CayChucNang::slug($item['title']) : $khoaCha;
    @endphp
    @continue($khoaCha !== null && $cap <= 2 && ! \App\Support\HeThong\PhanQuyenNguoiDung::coQuyen($khoaMuc))
    @php $dangChon = \App\Support\MenuQldt::dangChon($item); @endphp
    @if (! empty($item['children']))
        <div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle {{ $dangChon ? 'active' : '' }}" href="#">{{ $item['title'] }}</a>
            <div class="dropdown-menu">
                @include('PMGPLX.layouts.partials.menu-con', ['items' => $item['children'], 'khoaCha' => $khoaMuc, 'cap' => $cap + 1])
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
