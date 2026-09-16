@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập kết quả đào tạo')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
    $fileRows = $preview['records'] ?? [];
    $updates = $preview['updates'] ?? [];
    $skipped = $preview['skipped'] ?? [];
        $fieldLabels = \App\Support\DaoTao\KetQuaDaoTaoUpdater::FIELD_LABELS;
    $updateTotal = (int) ($preview['update_total'] ?? 0);
    $skipTotal = (int) ($preview['skip_total'] ?? 0);

    $formatVal = static function (string $field, mixed $value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($field === 'KetLuanCSDT') {
            return ((int) $value) === 1 ? 'Đạt' : 'Không đạt';
        }
        if (is_numeric($value) && ! in_array($field, ['NgayRaQDTN'], true)) {
            return rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',');
        }
        if ($field === 'NgayRaQDTN') {
            try {
                return \Carbon\Carbon::parse((string) $value)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    };
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập kết quả đào tạo</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div>
                <strong>Dòng file:</strong> {{ number_format((int) ($meta['record_count'] ?? 0)) }}
                · <strong>Sẽ cập nhật:</strong> {{ number_format($updateTotal) }}
                · <strong>Bỏ qua:</strong> {{ number_format($skipTotal) }}
            </div>
            <div class="mt-2">
                <span class="badge badge-success mr-1">{{ number_format((int) ($meta['dat_count'] ?? 0)) }} Đạt</span>
                <span class="badge badge-danger mr-1">{{ number_format((int) ($meta['khong_dat_count'] ?? 0)) }} Không đạt</span>
            </div>
            <p class="small text-muted mt-2 mb-0">
                TG/KM đường lấy từ Tổng giờ / Tổng KM máy chủ (Theo dõi DAT, chỉ phiên đạt).
                Kết luận CSDT: G/H từ file, P/Q từ DAT; B sàn G≥34 H≥120 P≥20 Q≥810; B tự động P≥12 Q≥710; C1 G≥35 H≥113 P≥24 Q≥830.
            </p>
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">
            Dữ liệu file — {{ count($fileRows) }} dòng đầu
            (tối đa {{ (int) ($meta['preview_limit'] ?? 5) }} / {{ number_format((int) ($meta['record_count'] ?? 0)) }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Dòng</th>
                            <th>STT</th>
                            <th>Mã HV</th>
                            <th>Họ tên</th>
                            <th>TG hình</th>
                            <th>KM hình</th>
                            <th>LT</th>
                            <th>Mô phỏng</th>
                            <th>KT hình</th>
                            <th>KT đường</th>
                            <th>Ngày HTKH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fileRows as $row)
                            <tr>
                                <td>{{ $row['excel_row'] ?? '' }}</td>
                                <td>{{ $row['stt'] ?? '' }}</td>
                                <td><code>{{ $row['ma_hoc_vien'] ?? '' }}</code></td>
                                <td>{{ $row['ho_ten'] ?? '' }}</td>
                                <td>{{ $formatVal('TGThucHanhHinh', $row['tg_thuc_hanh_hinh'] ?? null) }}</td>
                                <td>{{ $formatVal('QDThucHanhHinh', $row['qd_thuc_hanh_hinh'] ?? null) }}</td>
                                <td>{{ $formatVal('DiemKQLyThuyet', $row['diem_kq_ly_thuyet'] ?? null) }}</td>
                                <td>{{ $formatVal('DiemKQMoPhong', $row['diem_kq_mo_phong'] ?? null) }}</td>
                                <td>{{ $formatVal('DiemKQHinh', $row['diem_kq_hinh'] ?? null) }}</td>
                                <td>{{ $formatVal('DiemKQThucHanh', $row['diem_kq_thuc_hanh'] ?? null) }}</td>
                                <td>{{ $formatVal('NgayRaQDTN', $row['ngay_ra_kqtn'] ?? null) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-3">Không có dòng file.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">
            Cột sẽ cập nhật DB — hiển thị {{ count($updates) }}
            / {{ number_format($updateTotal) }} dòng
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Mã HV</th>
                            <th>Hạng</th>
                            <th>Nhóm</th>
                            @foreach ($fieldLabels as $field => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($updates as $row)
                            <tr @class(['table-warning' => ! empty($row['thieu_dat'])])>
                                <td>
                                    <code>{{ $row['ma_hoc_vien'] ?? '' }}</code>
                                    @if (! empty($row['thieu_dat']))
                                        <div class="small text-warning">Chưa có giờ/KM DAT</div>
                                    @endif
                                </td>
                                <td>{{ $row['hang_gplx'] !== '' ? $row['hang_gplx'] : '—' }}</td>
                                <td>{{ $row['nhom_label'] ?? '' }}</td>
                                @foreach ($fieldLabels as $field => $label)
                                    @php
                                        $moi = $row['payload'][$field] ?? null;
                                        $cu = $row['hien_tai'][$field] ?? null;
                                        $doi = (string) $moi !== (string) $cu;
                                    @endphp
                                    <td @class(['font-weight-bold' => $doi])>
                                        {{ $formatVal($field, $moi) }}
                                        @if ($doi)
                                            <div class="small text-muted">cũ: {{ $formatVal($field, $cu) }}</div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($fieldLabels) }}" class="text-center text-muted py-3">Không có dòng cập nhật.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($skipped !== [])
        <div class="card card-panel mb-3">
            <div class="card-header">Bỏ qua — {{ count($skipped) }} / {{ number_format($skipTotal) }}</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Dòng</th>
                                <th>Mã HV</th>
                                <th>Họ tên</th>
                                <th>Lý do</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($skipped as $row)
                                <tr>
                                    <td>{{ $row['excel_row'] ?? '' }}</td>
                                    <td><code>{{ $row['ma_hoc_vien'] ?? '' }}</code></td>
                                    <td>{{ $row['ho_ten'] ?? '' }}</td>
                                    <td>{{ $row['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.confirm') }}"
          onsubmit="return confirm('Cập nhật {{ number_format($updateTotal) }} hồ sơ NguoiLX_HoSo?');">
        @csrf
        <button type="submit" class="btn btn-success btn-lg" @disabled($updateTotal === 0)>
            Xác nhận lưu DB ({{ number_format($updateTotal) }} HV)
        </button>
        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.cancel') }}" class="btn btn-outline-secondary btn-lg ml-2">Hủy</a>
    </form>
@endsection
