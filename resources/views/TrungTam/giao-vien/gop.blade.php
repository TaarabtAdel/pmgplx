@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Gộp giáo viên')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Gộp giáo viên trùng</span>
            <a href="{{ route('trungtam.giao-vien.danh-sach') }}" class="btn btn-sm btn-outline-secondary">← Danh sách</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                Chọn <strong>giáo viên giữ lại</strong> (cột Giữ), tick các bản ghi trùng cần gộp.
                Bản ghi bị gộp sẽ <strong>xóa</strong>; phân công đào tạo chuyển sang ID giữ lại.
            </div>

            <form method="GET" action="{{ route('trungtam.giao-vien.gop') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="tu_khoa">Từ khóa</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="tu_khoa"
                               name="tu_khoa"
                               value="{{ $filters['tu_khoa'] }}"
                               placeholder="VD: Đỗ Minh Tứ">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="loai_gv">Loại GV</label>
                        <select class="form-control form-control-sm" id="loai_gv" name="loai_gv">
                            <option value="">— Tất cả —</option>
                            @foreach ($loaiGvOptions as $loai)
                                <option value="{{ $loai }}" @selected($filters['loai_gv'] === $loai)>{{ $loai }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm</button>
                    </div>
                </div>
            </form>

            <form method="POST"
                  action="{{ route('trungtam.giao-vien.gop.store') }}"
                  id="form-gop-gv"
                  onsubmit="return confirmGopGiaoVien();">
                @csrf

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center" style="width:3rem">Giữ</th>
                                <th class="text-center" style="width:3rem">Gộp</th>
                                <th>ID</th>
                                <th>Mã GV</th>
                                <th>Họ tên</th>
                                <th>Loại</th>
                                <th>Phân công</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $row)
                                <tr>
                                    <td class="text-center align-middle">
                                        <input type="radio"
                                               name="keep_id"
                                               value="{{ $row->Id }}"
                                               class="keep-radio"
                                               @checked((int) old('keep_id') === (int) $row->Id)
                                               required>
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox"
                                               name="merge_ids[]"
                                               value="{{ $row->Id }}"
                                               class="merge-checkbox"
                                               @checked(is_array(old('merge_ids')) && in_array((string) $row->Id, old('merge_ids', []), true))>
                                    </td>
                                    <td>{{ $row->Id }}</td>
                                    <td>{{ $row->MaGV ?: '—' }}</td>
                                    <td><strong>{{ $row->HoTen }}</strong></td>
                                    <td>{{ $row->LoaiGV ?: '—' }}</td>
                                    <td>{{ number_format($row->phan_cong_count) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Không tìm thấy giáo viên</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-warning" @disabled($items->isEmpty())>
                        Gộp giáo viên đã chọn
                    </button>
                    <a href="{{ route('trungtam.giao-vien.danh-sach') }}" class="btn btn-outline-secondary ml-2">Hủy</a>
                </div>
            </form>

            @include('TrungTam._pagination', ['items' => $items, 'filters' => $filters])
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function confirmGopGiaoVien() {
        var keep = document.querySelector('input[name="keep_id"]:checked');
        if (!keep) {
            alert('Chọn giáo viên sẽ giữ lại.');
            return false;
        }
        var merged = document.querySelectorAll('.merge-checkbox:checked');
        var mergeCount = 0;
        merged.forEach(function (el) {
            if (el.value !== keep.value) {
                mergeCount++;
            }
        });
        if (mergeCount < 1) {
            alert('Chọn ít nhất một giáo viên khác để gộp.');
            return false;
        }
        return confirm('Gộp ' + mergeCount + ' giáo viên vào bản ghi giữ lại? Các bản ghi bị gộp sẽ bị xóa.');
    }

    document.querySelectorAll('.keep-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.merge-checkbox').forEach(function (cb) {
                if (cb.value === radio.value) {
                    cb.checked = false;
                    cb.disabled = true;
                } else {
                    cb.disabled = false;
                }
            });
        });
    });

    document.querySelector('.keep-radio:checked')?.dispatchEvent(new Event('change'));
</script>
@endpush
