@php
    $bienSo = trim((string) ($bien_so_xe ?? ''));
    $bienTd = trim((string) ($bien_so_xe_tu_dong ?? ''));
@endphp
@if ($bienSo !== '' || $bienTd !== '')
    <span class="text-muted text-nowrap">
        @if ($bienSo !== '')
            Xe <strong>{{ $bienSo }}</strong>
        @endif
        @if ($bienSo !== '' && $bienTd !== '')
            ·
        @endif
        @if ($bienTd !== '')
            TĐ <strong>{{ $bienTd }}</strong>
        @endif
    </span>
@endif