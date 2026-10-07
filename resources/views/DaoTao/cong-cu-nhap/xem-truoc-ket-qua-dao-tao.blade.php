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
        if ($field === 'KetLuanCSDT') {
            if ($value === null || $value === '') {
                return '—';
            }

            return ((int) $value) === 1 ? 'Đạt' : 'Không đạt';
        }
        if ($field === '_num' || \App\Support\DaoTao\KetQuaDaoTaoUpdater::isNumericUpdateField($field)) {
            $num = \App\Support\DaoTao\KetQuaDaoTaoUpdater::asNumber($value);

            return rtrim(rtrim(number_format($num, 2, ',', ''), '0'), ',') ?: '0';
        }
        if ($value === null || $value === '') {
            return '—';
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

            @if (session('success'))
                <div class="alert alert-success small py-2 mt-2 mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger small py-2 mt-2 mb-0">{{ session('error') }}</div>
            @endif

            <div class="mt-3">
                <p class="small font-weight-bold mb-1">Ghi chú — cột file → trường phần mềm (<code>NguoiLX_HoSo</code>)</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small mb-2 bg-white">
                        <thead class="thead-light">
                            <tr>
                                <th>Tên trường (ký hiệu)</th>
                                <th>Cột Excel</th>
                                <th>Nguồn</th>
                                <th>Cột DB</th>
                            </tr>
                        </thead>
                        <tbody class="text-muted">
                            <tr>
                                <td>Thời gian thực hành hình (G)</td>
                                <td>G</td>
                                <td>File Excel</td>
                                <td><code>TGThucHanhHinh</code></td>
                            </tr>
                            <tr>
                                <td>Quãng đường thực hành hình (H)</td>
                                <td>H</td>
                                <td>File Excel</td>
                                <td><code>QDThucHanhHinh</code></td>
                            </tr>
                            <tr>
                                <td>Thời gian thực hành đường (P)</td>
                                <td>— <span class="text-warning">không đọc P trên file</span></td>
                                <td>Tổng giờ máy chủ DAT (phiên đạt)</td>
                                <td><code>TGThucHanhDuong</code></td>
                            </tr>
                            <tr>
                                <td>Quãng đường thực hành đường (Q)</td>
                                <td>— <span class="text-warning">không đọc Q trên file</span></td>
                                <td>Tổng km máy chủ DAT (phiên đạt)</td>
                                <td><code>TongQDThucHanh</code></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-1 bg-light">
                                    Các cột file khác: LT (I) → <code>DiemKQLyThuyet</code>;
                                    Mô phỏng (J) → <code>DiemKQMoPhong</code>;
                                    KT hình (K) → <code>DiemKQHinh</code>;
                                    KT đường (M) → <code>DiemKQThucHanh</code>;
                                    Ngày HTKH (O) → <code>NgayRaQDTN</code>.
                                </td>
                            </tr>
                            <tr>
                                <td>Kết luận tại CSDT</td>
                                <td>—</td>
                                <td>Tính tự động (xem bảng dưới)</td>
                                <td><code>KetLuanCSDT</code> (1 Đạt / 0 Không đạt)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small font-weight-bold mb-1">Kết luận CSDT — phải đủ cả 4 ngưỡng (theo hạng GPLX trên hồ sơ) và 4 KQ KT trên file (tạm thời &gt; 0)</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small mb-0 bg-white">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="2">Nhóm</th>
                                <th colspan="4">Thực hành / DAT (G, H, P, Q)</th>
                                <th colspan="4">KQ KT trên file (tạm thời &gt; 0)</th>
                            </tr>
                            <tr>
                                <th>TH hình (G) ≥</th>
                                <th>QD hình (H) ≥</th>
                                <th>TG đường (P) ≥</th>
                                <th>QD đường (Q) ≥</th>
                                <th>Lý thuyết (I)</th>
                                <th>TH đường (M)</th>
                                <th>Mô phỏng (J)</th>
                                <th>TH hình (K)</th>
                            </tr>
                        </thead>
                        <tbody class="text-muted">
                            <tr>
                                <td>B sàn</td>
                                <td>34</td><td>120</td><td>20</td><td>810</td>
                                <td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td>
                            </tr>
                            <tr>
                                <td>B tự động (B11)</td>
                                <td>34</td><td>120</td><td>12</td><td>710</td>
                                <td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td>
                            </tr>
                            <tr>
                                <td>C1</td>
                                <td>35</td><td>113</td><td>24</td><td>830</td>
                                <td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td><td>&gt; 0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-1">
                    Cột DB: <code>DiemKQLyThuyet</code>, <code>DiemKQThucHanh</code>, <code>DiemKQMoPhong</code>, <code>DiemKQHinh</code>.
                </p>
            </div>
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
                            <th class="text-nowrap">STT (A)</th>
                            <th class="text-nowrap">Mã HV (B)</th>
                            <th class="text-nowrap">Họ tên (C)</th>
                            <th class="text-nowrap">TG hình (G)</th>
                            <th class="text-nowrap">KM hình (H)</th>
                            <th class="text-nowrap">LT (I)</th>
                            <th class="text-nowrap">Mô phỏng (J)</th>
                            <th class="text-nowrap">KT hình (K)</th>
                            <th class="text-nowrap">KT đường (M)</th>
                            <th class="text-nowrap">Ngày HTKH (O)</th>
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

    @if (! empty($testMa) && is_array($testOne ?? null) && ($testOne['success'] ?? false))
        @include('DaoTao.cong-cu-nhap.partials.ket-qua-thu-mot-ket-qua', [
            'testOne' => $testOne,
            'formatVal' => $formatVal,
            'fieldLabels' => $fieldLabels,
        ])
    @elseif (! empty($testMa) && is_array($testOne ?? null) && ! ($testOne['success'] ?? false))
        <div class="alert alert-warning">{{ $testOne['message'] ?? 'Không tính được.' }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center">
        <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.confirm') }}" class="mr-2 mb-2"
              onsubmit="return confirm('Cập nhật {{ number_format($updateTotal) }} hồ sơ NguoiLX_HoSo?');">
            @csrf
            <button type="submit" class="btn btn-success btn-lg" @disabled($updateTotal === 0)>
                Xác nhận lưu DB ({{ number_format($updateTotal) }} HV)
            </button>
        </form>

        <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.confirm-one') }}"
              class="form-inline align-items-center border rounded px-3 py-2 bg-light mb-2 mr-2"
              onsubmit="return confirm('Lưu thử 1 học viên này vào NguoiLX_HoSo?');">
            @csrf
            <label class="small font-weight-bold mr-2 mb-0 text-nowrap" for="ma_hoc_vien">Thử trước 1 học viên</label>
            <input type="text" name="ma_hoc_vien" id="ma_hoc_vien" class="form-control form-control-sm mr-2"
                   style="min-width: 10rem;" placeholder="Mã HV (cột B)" value="{{ $testMa ?? '' }}" required autocomplete="off">
            <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">Lưu thử vào DB</button>
        </form>

        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.cancel') }}" class="btn btn-outline-secondary btn-lg mb-2">Hủy</a>
    </div>
@endsection
