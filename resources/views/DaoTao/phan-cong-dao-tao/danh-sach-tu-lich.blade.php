@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân công từ lịch PMGPLX')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Phân công từ lịch PMGPLX (đối chiếu)</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $tongHopUrl ?? route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop') }}" class="btn btn-sm btn-outline-success">Tổng hợp khoá · xe</a>
                <a href="{{ $compareManualUrl }}" class="btn btn-sm btn-outline-primary">← Phân công nhập tay</a>
                <a href="{{ route('pmgplx.lich.gv.index') }}" class="btn btn-sm btn-outline-secondary">Lịch giáo viên</a>
                <a href="{{ route('pmgplx.lich.xe.index') }}" class="btn btn-sm btn-outline-secondary">Lịch xe tập</a>
            </div>
        </div>
        <div class="card-body">
            <div class="small border rounded p-3 mb-3 bg-light">
                <p class="font-weight-bold mb-2">Ghi chú — logic màn đối chiếu</p>
                <p class="mb-2 text-muted">
                    Mục đích: xem <strong>từng dòng lịch</strong> trên PMGPLX (DB <code>GPLX_BAN_MOI</code>) để đối chiếu với
                    <a href="{{ $compareManualUrl }}">phân công nhập tay</a> — không ghi DB phân công MANHLINH.
                </p>
                <p class="mb-1"><strong>1. Hai nguồn dòng (gộp một bảng)</strong></p>
                <ul class="mb-2 pl-3">
                    <li>
                        <strong>Lịch GV</strong> (<code>KhoaHoc_GiaoVien</code>, nguồn cột «Lịch GV»):
                        chỉ <code>LoaiGV = LT</code>, có <code>NgayBD</code>.
                        Nội dung = loại GV + môn (nếu có). Loại giảng dạy = <em>Lý thuyết</em> (GVLT).
                    </li>
                    <li>
                        <strong>Lịch xe tập</strong> (<code>KhoaHoc_XeTap</code>, nguồn «Lịch xe»):
                        <code>IsKhoaHocXeTap = 0</code>, có <code>NgayBD</code> — cùng điều kiện cốt lõi với
                        <a href="{{ route('pmgplx.lich.xe.index') }}">/pmgplx/lich/xe-tap</a>.
                        Nội dung = «Thực hành» + ghi chú lịch. Loại = <em>Thực hành</em> (GVTH).
                        <strong>Không</strong> ẩn xe B11 / xe tự động tại màn này (hiển thị đủ như lịch xe).
                    </li>
                </ul>
                <p class="mb-1"><strong>2. Bộ lọc</strong></p>
                <ul class="mb-2 pl-3">
                    <li><code>ma_kh</code>, <code>ma_gv</code>, <code>bien_so_xe</code> — lấy danh mục từ PMGPLX (<code>KhoaHoc</code>, <code>GiaoVien</code>, <code>XeTap</code>).</li>
                    <li><code>loai</code>: «Tất cả» (LT + TH), «Chỉ lịch GV», «Chỉ lịch xe».</li>
                    <li>Tên khoá hiển thị từ <code>KhoaHoc.TenKH</code>; mã khoá trên lịch xe có thể khác hoa/thường — hệ thống chuẩn hóa khi ghép tên.</li>
                </ul>
                <p class="mb-1"><strong>3. Cột «Thời gian»</strong></p>
                <ul class="mb-2 pl-3">
                    <li>Mỗi dòng = một ca: <code>NgayBD</code> → bắt đầu, <code>NgayKT</code> → kết thúc.</li>
                    <li>Cùng ngày: hiển thị <code>dd/mm/yyyy HH:mm–HH:mm</code>; khác ngày: đủ ngày giờ hai đầu.</li>
                </ul>
                <p class="mb-1"><strong>4. Sắp xếp &amp; phân trang</strong></p>
                <ul class="mb-2 pl-3">
                    <li>Sắp theo ngày bắt đầu tăng dần, rồi tên khoá; 50 dòng/trang (slice sau khi gộp).</li>
                </ul>
                <p class="mb-0 text-muted">
                    <strong>Khác màn «Tổng hợp khoá · xe»:</strong> tổng hợp gom theo khoá + biển số, tối đa 4 GV;
                    và <em>có</em> lọc xe tự động (tên khoá có <strong>B01</strong> → giữ xe B11; khoá khác → ẩn xe B11 theo danh mục xe).
                </p>
            </div>

            @include('DaoTao.phan-cong-dao-tao.partials.loc-tu-lich-pmgplx', [
                'locAction' => route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich'),
                'showLoai' => true,
            ])

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
                            <th>Nguồn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $row['ten_khoa'] ?? '—' }}</strong>
                                    @if (! empty($row['ma_kh']))
                                        <div class="small text-muted">{{ $row['ma_kh'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $row['ho_ten_gv'] ?: '—' }}
                                    @if (! empty($row['ma_gv']))
                                        <div class="small text-muted">{{ $row['ma_gv'] }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @include('DaoTao.phan-cong-dao-tao.partials.thoi-gian-lich-pmgplx', [
                                        'tu' => $row['tu_ngay'] ?? null,
                                        'den' => $row['den_ngay'] ?? null,
                                    ])
                                </td>
                                <td>{{ $row['bien_so'] !== '' ? $row['bien_so'] : '—' }}</td>
                                <td>
                                    @if (! empty($row['loai_giang_day']))
                                        {{ \App\Models\DaoTao\PhanCongDaoTao::loaiGiangDayLabel($row['loai_giang_day']) }}
                                        <span class="text-muted small">({{ $row['loai_giang_day'] === 'ly_thuyet' ? 'GVLT' : 'GVTH' }})</span>
                                        @if (($row['noi_dung'] ?? '') !== '—')
                                            <div class="small">{{ $row['noi_dung'] }}</div>
                                        @endif
                                    @else
                                        {{ $row['noi_dung'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-nowrap small">
                                    @if (($row['nguon'] ?? '') === 'lich_gv')
                                        <a href="{{ route('pmgplx.lich.gv.index', array_filter(['ma_kh' => $row['ma_kh'] ?? '', 'ma_gv' => $row['ma_gv'] ?? ''])) }}">Lịch GV</a>
                                    @else
                                        <a href="{{ route('pmgplx.lich.xe.index', array_filter(['ma_kh' => $row['ma_kh'] ?? '', 'bien_so_xe' => $row['bien_so'] ?? '', 'ma_gv' => $row['ma_gv'] ?? ''])) }}">Lịch xe</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Không có dòng lịch phù hợp bộ lọc</td>
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
    @include('DaoTao.phan-cong-dao-tao.partials.scripts-loc-tu-lich-pmgplx')
@endpush
