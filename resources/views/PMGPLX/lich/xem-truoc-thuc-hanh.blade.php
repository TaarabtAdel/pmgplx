@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch thực hành')

@section('content')
    @php
        $conflictCount = (int) ($preview['meta']['conflict_count'] ?? 0);
        $okCount = (int) ($preview['meta']['ok_count'] ?? 0);
        $tenGvs = collect($preview['rows'] ?? [])
            ->map(fn ($r) => $r['TenGV'].' ('.$r['MaGV'].')')
            ->unique()
            ->values();
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước kết quả — Lịch xe tập lái</span>
            <a href="{{ route('pmgplx.lich.thuc-hanh.cancel') }}" class="btn btn-sm btn-outline-secondary">← Quay lại chỉnh sửa</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                @php
                    $ngayList = collect($preview['rows'] ?? [])
                        ->map(fn ($row) => \Carbon\Carbon::parse($row['NgayBD'])->format('d/m/Y'))
                        ->unique()
                        ->values();
                @endphp
                <div><strong>Khóa học:</strong> {{ $preview['meta']['ten_kh'] ?? '' }} ({{ $preview['form']['ma_kh'] ?? '' }})</div>
                <div><strong>Giáo viên:</strong> {{ $tenGvs->implode(', ') }}</div>
                <div><strong>Ngày:</strong> {{ $ngayList->implode(', ') }}</div>
                <div><strong>Giờ:</strong> {{ $preview['form']['gio_bd'] ?? '' }} → {{ $preview['form']['gio_kt'] ?? '' }}</div>
                <div><strong>Địa điểm:</strong> {{ $preview['form']['dia_diem'] ?? '' }}</div>
                <div><strong>Tổng buổi:</strong> {{ count($preview['rows']) }} —
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-danger">{{ $conflictCount }}</strong> trùng
                </div>
            </div>

            @if ($conflictCount > 0)
                <div class="alert alert-warning">
                    Các dòng nền đỏ đã có trong lịch (trùng giáo viên hoặc xe). Khi xác nhận sẽ <strong>không lưu</strong> các dòng này.
                </div>
            @endif

            <div class="alert alert-info">
                Xe tập được điền sẵn theo gắn xe ở <strong>Quản lý giáo viên</strong>. Bạn có thể chọn lại trước khi lưu.
            </div>

            <form method="POST" action="{{ route('pmgplx.lich.thuc-hanh.confirm') }}" id="formConfirmThucHanh">
                @csrf
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th style="min-width: 11rem;">Xe tập</th>
                                <th>Giáo viên</th>
                                <th>Địa điểm</th>
                                <th>TG bắt đầu</th>
                                <th>TG kết thúc</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['rows'] as $i => $row)
                                <tr class="{{ !empty($row['conflict']) ? 'table-danger' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <select
                                            name="bien_so_xe[{{ $i }}]"
                                            class="form-control form-control-sm select-xe-preview"
                                            data-ma-gv="{{ $row['MaGV'] }}"
                                            @disabled(!empty($row['conflict']))
                                            @required(empty($row['conflict']))
                                        >
                                            <option value="">-- Chọn xe --</option>
                                            @foreach ($xeTaps as $xe)
                                                <option value="{{ $xe }}" @selected(($row['BienSoXe'] ?? '') === $xe)>{{ $xe }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>{{ $row['TenGV'] }} ({{ $row['MaGV'] }})</td>
                                    <td>{{ $row['DiaDiem'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['NgayBD'])->format('d/m/Y H:i') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['NgayKT'])->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if (!empty($row['conflict']))
                                            <span class="text-danger font-weight-bold">Đã thêm vào lịch</span>
                                        @elseif (trim((string) ($row['BienSoXe'] ?? '')) === '')
                                            <span class="text-warning font-weight-bold">Chưa gắn xe</span>
                                        @else
                                            <span class="text-success">Sẽ lưu mới</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($okCount > 0)
                    <button type="submit" class="btn btn-success btn-lg">
                        Xác nhận lưu {{ $okCount }} buổi
                    </button>
                @else
                    <button type="button" class="btn btn-secondary btn-lg" disabled>Không có buổi nào để lưu</button>
                @endif
                <a href="{{ route('pmgplx.lich.thuc-hanh.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.select-xe-preview').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm biển số xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $(document.body)
    });

    // Đổi xe của một dòng → đồng bộ các dòng cùng giáo viên (chưa trùng)
    $(document).on('change', '.select-xe-preview', function () {
        var $src = $(this);
        var maGv = $src.data('ma-gv');
        var val = $src.val() || '';
        $('.select-xe-preview').not($src).each(function () {
            var $el = $(this);
            if (String($el.data('ma-gv')) === String(maGv) && !$el.prop('disabled')) {
                $el.val(val).trigger('change.select2');
            }
        });
    });
</script>
@endpush
