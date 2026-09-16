@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập XML xuất biên bản tổng hợp')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header">Nhập XML kết quả sát hạch</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file XML <code>&lt;SAT_HACH&gt;</code>. Hệ thống lưu từng thí sinh vào bảng <code>SatHachBienBan</code> (DB MANHLINH).
                Sau đó xuất <strong>từng biên bản DOCX</strong> — mỗi lần một thí sinh, nhẹ hơn file gộp hàng trăm trang.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-6 mb-2">
                        <label for="file">Chọn file XML</label>
                        <input type="file" name="file" id="file" class="form-control-file" accept=".xml,text/xml" required>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <button type="submit" class="btn btn-navy">Nhập vào DB</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách thí sinh đã nhập</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label class="small text-muted mb-1" for="ma_ky_sh">Kỳ sát hạch</label>
                        <select name="ma_ky_sh" id="ma_ky_sh" class="form-control form-control-sm" data-placeholder="— Tất cả —">
                            <option value=""></option>
                            @foreach ($kyOptions as $ky)
                                <option value="{{ $ky }}" @selected(($filters['ma_ky_sh'] ?? '') === $ky)>{{ $ky }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label class="small text-muted mb-1" for="tu_khoa">Tìm (tên / SBD / mã ĐK / CCCD)</label>
                        <input type="text" name="tu_khoa" id="tu_khoa" class="form-control form-control-sm"
                               value="{{ $filters['tu_khoa'] ?? '' }}">
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <button type="submit" class="btn btn-sm btn-navy mr-1">Lọc</button>
                        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>Kỳ SH</th>
                            <th>SBD</th>
                            <th>Họ tên</th>
                            <th>Mã ĐK</th>
                            <th>Hạng</th>
                            <th>Ngày sinh</th>
                            <th>Ảnh</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $row)
                            <tr>
                                <td>{{ $row->SoTT ?: '—' }}</td>
                                <td><code>{{ $row->MaKySH ?: '—' }}</code></td>
                                <td>{{ $row->SoBaoDanh ?: '—' }}</td>
                                <td>{{ $row->HoVaTen ?: '—' }}</td>
                                <td><code>{{ $row->MaDK }}</code></td>
                                <td>{{ $row->HangGPLX ?: '—' }}</td>
                                <td>{{ $row->NgaySinh ?: '—' }}</td>
                                <td>
                                    @if ((int) $row->CoAnh === 1)
                                        <span class="badge badge-success">Có</span>
                                    @else
                                        <span class="badge badge-secondary">Không</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export', $row->Id) }}"
                                       class="btn btn-sm btn-outline-success">
                                        Xuất DOCX
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chưa có dữ liệu. Hãy nhập file XML.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 mb-0">
                {{ $items->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#ma_ky_sh').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '100%',
            placeholder: '— Tất cả —'
        });
    });
</script>
@endpush
