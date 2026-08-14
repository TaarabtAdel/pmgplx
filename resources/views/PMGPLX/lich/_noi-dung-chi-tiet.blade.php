@once
@push('styles')
<style>
    th.col-noi-dung-chi-tiet,
    td.col-noi-dung-chi-tiet {
        font-size: 0.75rem;
        line-height: 1.25;
    }
    th.col-noi-dung-chi-tiet {
        font-size: 0.78rem;
    }
</style>
@endpush
@endonce

@php
    $text = \App\Support\PMGPLX\LichExcelNoiDungSkip::format(
        (string) ($noiDung ?? ''),
        (string) ($chiTiet ?? '')
    );
@endphp
{{ $text }}
