@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Danh sách phân công')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Danh sách phân công đào tạo</span>
            @if (! empty($backToLuuLuongUrl))
                <a href="{{ $backToLuuLuongUrl }}" class="btn btn-sm btn-outline-secondary">← Báo cáo lưu lượng</a>
            @endif
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}">
                @foreach ($luuLuongBackParams ?? [] as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="khoa_dao_tao_id">Khoá đào tạo</label>
                        <select class="form-control form-control-sm" id="khoa_dao_tao_id" name="khoa_dao_tao_id">
                            <option value="">— Tất cả —</option>
                            @foreach ($khoaOptions as $khoa)
                                <option value="{{ $khoa->Id }}" @selected((int) ($filters['khoa_dao_tao_id'] ?? 0) === (int) $khoa->Id)>{{ $khoa->TenKhoa }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="giao_vien_id">Giáo viên</label>
                        <select class="form-control form-control-sm" id="giao_vien_id" name="giao_vien_id">
                            <option value="">— Tất cả —</option>
                            @foreach ($giaoVienOptions as $gv)
                                <option value="{{ $gv->Id }}" @selected((int) ($filters['giao_vien_id'] ?? 0) === (int) $gv->Id)>{{ $gv->HoTen }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="xe_tap_lai_id">Biển số xe</label>
                        <select class="form-control form-control-sm" id="xe_tap_lai_id" name="xe_tap_lai_id">
                            <option value="">— Tất cả —</option>
                            @foreach ($xeOptions as $xe)
                                <option value="{{ $xe->Id }}" @selected((int) ($filters['xe_tap_lai_id'] ?? 0) === (int) $xe->Id)>{{ $xe->BienSo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Lọc</button>
                        <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}"
                           class="btn btn-sm btn-outline-secondary btn-block mt-1"
                           title="Làm mới">↻</a>
                    </div>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mt-2">
                <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', array_merge($listQueryParams ?? [], ['check_gv' => 'all'])) }}"
                   class="btn btn-sm {{ ($checkGiaoVienAll ?? false) ? 'btn-warning' : 'btn-outline-warning' }}">
                    Kiểm tra trùng lịch — tất cả giáo viên
                </a>
                <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', array_merge($listQueryParams ?? [], ['check_xe' => 'all'])) }}"
                   class="btn btn-sm {{ ($checkXeAll ?? false) ? 'btn-warning' : 'btn-outline-warning' }}">
                    Kiểm tra trùng lịch — tất cả xe
                </a>
                @if (($checkGiaoVienAll ?? false) || ($checkXeAll ?? false))
                    @php
                        $clearBulkCheckParams = collect($listQueryParams ?? [])->except(['check_gv', 'check_xe'])->all();
                    @endphp
                    <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', $clearBulkCheckParams) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Tắt kiểm tra hàng loạt
                    </a>
                @endif
            </div>
            <p class="small text-muted mb-0 mt-1">
                Chọn 1 giáo viên/xe trong bộ lọc để kiểm tra riêng, hoặc dùng nút trên để quét toàn hệ thống.
                Trùng lịch chỉ tính trong cùng loại giảng dạy (<strong>lý thuyết</strong> / <strong>thực hành</strong>).
            </p>

            @if ($giaoVienScheduleChecked ?? false)
                @if (($giaoVienOverlapWarnings ?? []) !== [])
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Cảnh báo — trùng lịch giữa các khoá</strong>
                        ({{ $giaoVienCheckScope ?? ($selectedGiaoVien?->HoTen ?? '—') }}):
                        <ul class="mb-0 pl-3 mt-2">
                            @foreach ($giaoVienOverlapWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success mt-3 mb-0">
                        <strong>Khoảng thời gian giáo viên hợp lệ</strong>
                        — không có trùng lịch giữa các khoá
                        ({{ $giaoVienCheckScope ?? ($selectedGiaoVien?->HoTen ?? '—') }}).
                    </div>
                @endif
            @endif

            @if ($xeScheduleChecked ?? false)
                @if (($xeOverlapWarnings ?? []) !== [])
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Cảnh báo — trùng lịch giữa các khoá</strong>
                        ({{ $xeCheckScope ?? ($selectedXeTapLai?->BienSo ?? '—') }}):
                        <ul class="mb-0 pl-3 mt-2">
                            @foreach ($xeOverlapWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success mt-3 mb-0">
                        <strong>Khoảng thời gian xe hợp lệ</strong>
                        — không có trùng lịch giữa các khoá
                        ({{ $xeCheckScope ?? ($selectedXeTapLai?->BienSo ?? '—') }}).
                    </div>
                @endif
            @endif

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khoá</th>
                            <th>Giáo viên</th>
                            <th>Thời gian</th>
                            <th>Biển số xe</th>
                            <th>Nội dung giảng dạy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td><strong>{{ $row->khoaDaoTao?->TenKhoa ?? '—' }}</strong></td>
                                <td>{{ $row->giaoVien?->HoTen ?? '—' }}</td>
                                <td>
                                    @if ($row->TuNgay && $row->DenNgay)
                                        {{ $row->TuNgay->format('d/m/Y') }} – {{ $row->DenNgay->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->xeTapLai?->BienSo ?? '—' }}</td>
                                <td>
                                    @if ($row->LoaiGiangDay)
                                        {{ \App\Models\DaoTao\PhanCongDaoTao::loaiGiangDayLabel($row->LoaiGiangDay) }}
                                        <span class="text-muted small">({{ $row->LoaiGiangDay }})</span>
                                    @else
                                        {{ $row->NoiDungGiangDay ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Chưa có dữ liệu phân công</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
                        · {{ number_format($items->total()) }} dòng
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->url(1) }}">«</a>
                            </li>
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">{{ $items->currentPage() }}</span>
                            </li>
                            <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $items->nextPageUrl() }}">›</a>
                            </li>
                            <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $items->url($items->lastPage()) }}">»</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#khoa_dao_tao_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khoá đào tạo...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#khoa_dao_tao_id').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy khoá'; } }
    });

    $('#giao_vien_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#giao_vien_id').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy giáo viên'; } }
    });

    $('#xe_tap_lai_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm biển số xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#xe_tap_lai_id').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy biển số xe'; } }
    });
</script>
@endpush
