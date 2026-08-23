@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Trung tâm — Xe tập lái')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Xe tập lái — Trung tâm (DB MANHLINH)</div>
        <div class="card-body">
            <form method="GET" action="{{ route('trungtam.xe-tap-lai.danh-sach') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label for="tu_khoa">Từ khóa</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="tu_khoa"
                               name="tu_khoa"
                               value="{{ $filters['tu_khoa'] }}"
                               placeholder="Biển số, loại xe, hãng xe">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="trang_thai">Trạng thái</label>
                        <select class="form-control form-control-sm" id="trang_thai" name="trang_thai">
                            <option value="">— Tất cả —</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Đang sử dụng</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Ngừng</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Lọc</button>
                        <a href="{{ route('trungtam.xe-tap-lai.danh-sach') }}"
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
                            <th>Biển số</th>
                            <th>Loại xe</th>
                            <th>Hãng xe</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', ['xe_tap_lai_id' => $row->Id]) }}">
                                        <strong>{{ $row->BienSo }}</strong>
                                    </a>
                                </td>
                                <td>{{ $row->LoaiXe ?: '—' }}</td>
                                <td>{{ $row->HangXe ?: '—' }}</td>
                                <td>
                                    @if ($row->TrangThai)
                                        <span class="text-success">Đang dùng</span>
                                    @else
                                        <span class="text-muted">Ngừng</span>
                                    @endif
                                </td>
                                <td class="small">{{ $row->GhiChu ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Chưa có dữ liệu xe tập lái</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('TrungTam._pagination', ['items' => $items, 'filters' => $filters])
        </div>
    </div>
@endsection
