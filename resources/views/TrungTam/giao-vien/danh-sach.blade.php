@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Trung tâm — Giáo viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Giáo viên — Trung tâm (DB MANHLINH)</span>
            <a href="{{ route('trungtam.giao-vien.gop') }}" class="btn btn-sm btn-warning">Gộp giáo viên</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('trungtam.giao-vien.danh-sach') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="tu_khoa">Từ khóa</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="tu_khoa"
                               name="tu_khoa"
                               value="{{ $filters['tu_khoa'] }}"
                               placeholder="Mã GV, họ tên, SĐT, loại GV">
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
                        <label for="trang_thai">Trạng thái</label>
                        <select class="form-control form-control-sm" id="trang_thai" name="trang_thai">
                            <option value="">— Tất cả —</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Đang hoạt động</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Ngừng</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Lọc</button>
                        <a href="{{ route('trungtam.giao-vien.danh-sach') }}"
                           class="btn btn-sm btn-outline-secondary btn-block mt-1"
                           title="Làm mới">↻</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã GV</th>
                            <th>Họ tên</th>
                            <th>Loại GV</th>
                            <th>Số ĐT</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->MaGV ?: '—' }}</td>
                                <td>
                                    <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', ['giao_vien_id' => $row->Id]) }}">
                                        <strong>{{ $row->HoTen }}</strong>
                                    </a>
                                </td>
                                <td>{{ $row->LoaiGV ?: '—' }}</td>
                                <td>{{ $row->SoDienThoai ?: '—' }}</td>
                                <td>
                                    @if ($row->TrangThai)
                                        <span class="text-success">Hoạt động</span>
                                    @else
                                        <span class="text-muted">Ngừng</span>
                                    @endif
                                </td>
                                <td class="small">{{ $row->GhiChu ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Chưa có dữ liệu giáo viên</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('TrungTam._pagination', ['items' => $items, 'filters' => $filters])
        </div>
    </div>
@endsection
