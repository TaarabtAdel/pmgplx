@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <p class="text-muted small mb-3">
                Chọn khoá trên PMGPLX, xuất file Excel <strong>cùng cấu trúc</strong> với
                <a href="{{ $linkNhapLich }}">Nhập lịch từ file</a>
                (7 cột / giáo viên: Thứ, ngày, Số giờ, Nội dung, Chi tiết, Bắt đầu, Kết thúc).
                <strong>Mỗi giáo viên một sheet</strong>, tên sheet = tên giáo viên.
                Dữ liệu lấy từ <a href="{{ $linkLichGv }}" target="_blank">PMGPLX → Lịch giáo viên</a>.
            </p>

            <form method="GET" action="{{ route('qldtoto.lich-hoc-lt-th-tung-giao-vien.index') }}" class="form-row align-items-end mb-3" id="form-chon-khoa">
                <div class="form-group col-md-5 mb-0">
                    <label for="ma_kh">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm sel-khoa-pmgplx" id="ma_kh" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>
                                {{ $kh->TenKH }} ({{ $kh->MaKH }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Chọn khoá</button>
                </div>
            </form>

            @if ($maKh === '')
                <p class="text-muted mb-0">Chọn khoá để xem giáo viên và xuất Excel.</p>
            @else
                @if ($tenKhoa !== '')
                    <p class="small mb-2"><strong>Khoá:</strong> {{ $tenKhoa }} <span class="text-muted">({{ $maKh }})</span></p>
                @endif

                @if ($danhSachGv === [])
                    <div class="alert alert-warning py-2 small mb-3">
                        Chưa có buổi lịch giáo viên cho khoá này —
                        nhập hoặc kiểm tra tại <a href="{{ $linkLichGv }}" target="_blank">lịch giáo viên</a>.
                    </div>
                @else
                    <h6 class="small font-weight-bold mb-2">Giáo viên trong khoá ({{ count($danhSachGv) }})</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Giáo viên</th>
                                    <th>Xe tập lái</th>
                                    <th class="text-right">Buổi lịch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($danhSachGv as $gv)
                                    <tr>
                                        <td>{{ $gv['ten_gv'] }} <span class="text-muted">({{ $gv['ma_gv'] }})</span></td>
                                        <td>{{ $gv['bien_so'] !== '' ? $gv['bien_so'] : '—' }}</td>
                                        <td class="text-right">{{ count($gv['buoi'] ?? []) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('qldtoto.lich-hoc-lt-th-tung-giao-vien.export-excel') }}">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $maKh }}">
                    <button type="submit" class="btn btn-success btn-sm" @disabled($danhSachGv === [])>
                        ⬇ Xuất Excel (mỗi GV một sheet)
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    const $khoa = $('#ma_kh');
    if (!$khoa.length) return;
    $khoa.select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: $khoa.data('placeholder') || '— Chọn khoá —',
        dropdownParent: $khoa.closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
    });
})(jQuery);
</script>
@endpush