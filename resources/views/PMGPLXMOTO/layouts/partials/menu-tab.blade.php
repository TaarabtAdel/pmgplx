{{-- Một tab menu chính vẽ từ CayChucNang ($tab), ẩn mục không có quyền. --}}
@php
    use App\Support\HeThong\CayChucNang;
    use App\Support\HeThong\PhanQuyenNguoiDung;

    $dangMo = fn (array $node): bool => ($mau = CayChucNang::mauRoute($node)) !== [] && request()->routeIs(...$mau);
    $daHien = 0;
@endphp
@if (PhanQuyenNguoiDung::coQuyen($tab['khoa']))
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle {{ $dangMo($tab) ? 'active' : '' }}"
           href="#"
           id="nav-{{ str_replace('/', '-', $tab['khoa']) }}"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            {{ $tab['title'] }}
        </a>
        <div class="dropdown-menu" aria-labelledby="nav-{{ str_replace('/', '-', $tab['khoa']) }}">
            @foreach ($tab['children'] as $m1)
                @continue(! PhanQuyenNguoiDung::coQuyen($m1['khoa']))
                @if ($m1['divider'] && $daHien > 0)
                    <div class="dropdown-divider"></div>
                @endif
                @php $daHien++; @endphp
                @if ($m1['children'] !== [])
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle {{ $dangMo($m1) ? 'active' : '' }}" href="#">{{ $m1['title'] }}</a>
                        <div class="dropdown-menu">
                            @php $daHien2 = 0; @endphp
                            @foreach ($m1['children'] as $m2)
                                @continue(! PhanQuyenNguoiDung::coQuyen($m2['khoa']))
                                @if ($m2['divider'] && $daHien2 > 0)
                                    <div class="dropdown-divider"></div>
                                @endif
                                @php $daHien2++; @endphp
                                <a class="dropdown-item {{ $dangMo($m2) ? 'active' : '' }}" href="{{ route($m2['route']) }}">{{ $m2['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a class="dropdown-item {{ $dangMo($m1) ? 'active' : '' }}" href="{{ route($m1['route']) }}">{{ $m1['title'] }}</a>
                @endif
            @endforeach
        </div>
    </li>
@endif
