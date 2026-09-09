@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân loại phiên DAT')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Phân loại phiên DAT</span>
            <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Tạo danh mục phân loại (giống danh mục bài viết). Sau đó tại <strong>Quản lý phiên</strong>,
                tick nhiều phiên và gán vào các phân loại đã tạo.
            </p>

            <form method="POST" action="{{ route('daotao.pdt.dat.phan-loai-phien.store') }}" class="mb-4 border rounded p-3 bg-light">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-md-0">
                        <label>Tên phân loại <span class="text-danger">*</span></label>
                        <input type="text" name="ten_phan_loai" class="form-control" maxlength="150" required
                               placeholder="VD: Phiên hợp lệ, Cần xử lý…">
                    </div>
                    <div class="form-group col-md-4 mb-md-0">
                        <label>Mô tả</label>
                        <input type="text" name="mo_ta" class="form-control" maxlength="500">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label>Thứ tự</label>
                        <input type="number" name="thu_tu" class="form-control" min="0" value="0">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <button type="submit" class="btn btn-navy btn-block">Thêm</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="80">Thứ tự</th>
                            <th>Tên phân loại</th>
                            <th>Mô tả</th>
                            <th width="90">Số phiên</th>
                            <th width="140">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td colspan="5" class="p-0">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td width="80" class="border-0">
                                                <form method="POST" action="{{ route('daotao.pdt.dat.phan-loai-phien.update', $item->Id) }}" id="frm-pl-update-{{ $item->Id }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" name="thu_tu" class="form-control form-control-sm"
                                                           min="0" value="{{ $item->ThuTu ?? 0 }}" form="frm-pl-update-{{ $item->Id }}">
                                                </form>
                                            </td>
                                            <td class="border-0">
                                                <input type="text" name="ten_phan_loai" class="form-control form-control-sm"
                                                       maxlength="150" value="{{ $item->TenPhanLoai }}" required
                                                       form="frm-pl-update-{{ $item->Id }}">
                                            </td>
                                            <td class="border-0">
                                                <input type="text" name="mo_ta" class="form-control form-control-sm"
                                                       maxlength="500" value="{{ $item->MoTa }}"
                                                       form="frm-pl-update-{{ $item->Id }}">
                                            </td>
                                            <td width="90" class="border-0 text-center align-middle">
                                                {{ number_format($item->phien_hoc_count ?? 0) }}
                                            </td>
                                            <td width="140" class="border-0 align-middle text-nowrap">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" form="frm-pl-update-{{ $item->Id }}">Lưu</button>
                                                <form method="POST" action="{{ route('daotao.pdt.dat.phan-loai-phien.destroy', $item->Id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Xóa phân loại «{{ $item->TenPhanLoai }}»? Các phiên chỉ bỏ gán, không bị xóa.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Chưa có phân loại. Thêm danh mục ở form phía trên.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
