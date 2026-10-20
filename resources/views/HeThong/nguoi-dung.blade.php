@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Người dùng')

@php
    $oldCreate = old('_form') === 'create';
    $oldEdit = old('_form') === 'edit';
    $oldVaiTro = (array) old('vai_tro', []);
@endphp

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thêm người dùng</div>
        <div class="card-body">
            <form method="POST" action="{{ route('hethong.nguoi-dung.store') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Tên <span class="text-danger">*</span></label>
                        <input type="text" name="ten" class="form-control form-control-sm" maxlength="150" required
                               value="{{ $oldCreate ? old('ten') : '' }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>SĐT <span class="text-danger">*</span></label>
                        <input type="text" name="so_dien_thoai" class="form-control form-control-sm" maxlength="20" required
                               value="{{ $oldCreate ? old('so_dien_thoai') : '' }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="mat_khau" class="form-control form-control-sm" minlength="6" maxlength="100" required
                               autocomplete="new-password">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="d-block">Vai trò <span class="text-danger">*</span></label>
                        @foreach ($vaiTroOptions as $ma => $ten)
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input" id="tao_vt_{{ $ma }}" name="vai_tro[]" value="{{ $ma }}"
                                       @checked($oldCreate && in_array($ma, $oldVaiTro, true))>
                                <label class="custom-control-label" for="tao_vt_{{ $ma }}">{{ $ten }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-navy btn-block">＋ Thêm</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách người dùng</div>
        <div class="card-body">
            <form method="GET" action="{{ route('hethong.nguoi-dung.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-3 mb-0">
                    <label>Tên|SĐT</label>
                    <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
                </div>
                <div class="form-group col-md-3 mb-0">
                    <label>Vai trò</label>
                    <select class="form-control form-control-sm" name="vai_tro">
                        <option value="">—Tất cả—</option>
                        @foreach ($vaiTroOptions as $ma => $ten)
                            <option value="{{ $ma }}" @selected($filters['vai_tro'] === $ma)>{{ $ten }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                </div>
                <div class="col ml-auto text-right">
                    Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th width="60">STT</th>
                            <th>Tên</th>
                            <th>SĐT</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <th>Cập nhật</th>
                            <th width="140">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->Ten }}</td>
                                <td>{{ $row->SoDienThoai }}</td>
                                <td>
                                    @foreach ($row->tenVaiTroList() as $tenVaiTro)
                                        <span class="badge badge-info">{{ $tenVaiTro }}</span>
                                    @endforeach
                                </td>
                                <td class="text-nowrap">{{ $row->NgayTao?->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">{{ $row->NgayCapNhat?->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap js-no-row-select">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-sua-nguoi-dung"
                                            data-ten="{{ $row->Ten }}"
                                            data-sdt="{{ $row->SoDienThoai }}"
                                            data-vai-tro='@json($row->maVaiTroList())'
                                            data-action="{{ route('hethong.nguoi-dung.update', $row->Id) }}">Sửa</button>
                                    <form method="POST" action="{{ route('hethong.nguoi-dung.destroy', $row->Id) }}" class="d-inline"
                                          onsubmit="return confirm(@js('Xóa người dùng «'.$row->Ten.'»?'));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Chưa có người dùng.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="mt-3">{{ $items->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalSuaNguoiDung" tabindex="-1" role="dialog" aria-labelledby="modalSuaNguoiDungLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ $oldEdit ? old('_action') : '' }}" class="modal-content" id="formSuaNguoiDung" autocomplete="off">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_action" id="sua_action" value="{{ old('_action') }}">
                <input type="hidden" name="tu_khoa" value="{{ $filters['tu_khoa'] }}">
                <input type="hidden" name="vai_tro_loc" value="{{ $filters['vai_tro'] }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSuaNguoiDungLabel">Sửa người dùng</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên <span class="text-danger">*</span></label>
                        <input type="text" name="ten" id="sua_ten" class="form-control" maxlength="150" required
                               value="{{ $oldEdit ? old('ten') : '' }}">
                    </div>
                    <div class="form-group">
                        <label>SĐT <span class="text-danger">*</span></label>
                        <input type="text" name="so_dien_thoai" id="sua_sdt" class="form-control" maxlength="20" required
                               value="{{ $oldEdit ? old('so_dien_thoai') : '' }}">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="mat_khau" class="form-control" minlength="6" maxlength="100" autocomplete="new-password">
                        <small class="form-text text-muted">Để trống nếu giữ nguyên mật khẩu cũ.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="d-block">Vai trò <span class="text-danger">*</span></label>
                        @foreach ($vaiTroOptions as $ma => $ten)
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input sua-vai-tro" id="sua_vt_{{ $ma }}" name="vai_tro[]" value="{{ $ma }}"
                                       @checked($oldEdit && in_array($ma, $oldVaiTro, true))>
                                <label class="custom-control-label" for="sua_vt_{{ $ma }}">{{ $ten }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var $modal = $('#modalSuaNguoiDung');
        var $form = $('#formSuaNguoiDung');

        $(document).on('click', '.btn-sua-nguoi-dung', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var action = $btn.attr('data-action') || '';
            var vaiTros = [];
            try {
                vaiTros = JSON.parse($btn.attr('data-vai-tro') || '[]');
            } catch (err) {
                vaiTros = [];
            }
            $form.attr('action', action);
            $('#sua_action').val(action);
            $('#sua_ten').val($btn.attr('data-ten') || '');
            $('#sua_sdt').val($btn.attr('data-sdt') || '');
            $form.find('.sua-vai-tro').each(function () {
                this.checked = vaiTros.indexOf(this.value) !== -1;
            });
            $form.find('input[name="mat_khau"]').val('');
            $modal.modal('show');
        });

        @if ($oldEdit && $errors->any())
            $modal.modal('show');
        @endif
    })();
</script>
@endpush
