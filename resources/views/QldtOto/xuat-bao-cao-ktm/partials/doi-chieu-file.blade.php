@php
    $g = $duLieu->tongHopBTTml;
        $tyLe = $g->tyLeDat($form['bbth']['ty_le_mau_so'] ?? $form['ty_le_mau_so'] ?? 'du_thi');
    $ghiChuDem = [];
    foreach ($duLieu->thiSinh as $t) {
        $k = trim($t->ghiChu) !== '' ? $t->ghiChu : '(trống)';
        $ghiChuDem[$k] = ($ghiChuDem[$k] ?? 0) + 1;
    }
    ksort($ghiChuDem, SORT_NATURAL);
    $vang = array_filter($duLieu->thiSinh, fn ($t) => ! $t->coMat);
    $dat = array_filter($duLieu->thiSinh, fn ($t) => $t->tongHop === 'Đ');
    $kd = array_filter($duLieu->thiSinh, fn ($t) => $t->coMat && $t->tongHop === 'KĐ');
@endphp

<div class="card card-panel mb-3">
    <div class="card-header py-2 d-flex flex-wrap align-items-center gap-2">
        <span>Thông tin đọc từ file</span>
        <span class="badge badge-secondary font-weight-normal">{{ count($duLieu->thiSinh) }} thí sinh trong DS SH</span>
    </div>
    <div class="card-body small pb-2">
        <dl class="row mb-2 doi-chieu-meta">
            <dt class="col-sm-3">File</dt>
            <dd class="col-sm-9 mb-1"><strong>{{ $fileName }}</strong></dd>
            <dt class="col-sm-3">DS SH · C7</dt>
            <dd class="col-sm-9 mb-1">{{ $duLieu->tenKhoa ?: '—' }}</dd>
            <dt class="col-sm-3">DS SH · C8</dt>
            <dd class="col-sm-9 mb-1">{{ $duLieu->ngayThi ?: '—' }}</dd>
            <dt class="col-sm-3">DS SH · G7</dt>
            <dd class="col-sm-9 mb-1">
                {{ $duLieu->g7TuFile ?: '—' }}
                <span class="text-muted">· đếm danh sách hàng 11+: <strong>{{ count($duLieu->thiSinh) }}</strong></span>
                @if ($duLieu->g7TuFile > 0 && $duLieu->g7TuFile !== count($duLieu->thiSinh))
                    <span class="text-warning"> (lệch G7)</span>
                @endif
            </dd>
        </dl>

        <p class="text-muted mb-2" style="font-size: .78rem;">
            Kết quả từng người: <strong>Sheet1</strong> (cột W–AA, khóa CCCD = cột D) · Thứ tự in <strong>BC CHI TIẾT</strong> = STT trong <strong>DS SH</strong> (hàng 11+).
        </p>

        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-2">
                    <caption class="p-0 text-muted" style="caption-side: top; font-size: .78rem;">Tổng hợp tính để xuất BBTH / BC CHUNG (nhóm B TTML)</caption>
                    <tbody>
                        <tr>
                            <td>Hồ sơ</td><td class="text-right"><strong>{{ $g->hoSo }}</strong></td>
                            <td>Dự thi</td><td class="text-right"><strong>{{ $g->duThi }}</strong></td>
                            <td>Vắng</td><td class="text-right"><strong>{{ $g->vang }}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="2">Kết quả đạt</td>
                            <td colspan="2" class="text-right"><strong>{{ $g->ketQuaDat }}</strong></td>
                            <td>Tỷ lệ</td>
                            <td class="text-right"><strong>{{ number_format($tyLe, 1, ',', '') }}%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-2">
                    <caption class="p-0 text-muted" style="caption-side: top; font-size: .78rem;">Theo nội dung (chỉ người có mặt &amp; phải thi)</caption>
                    <thead class="thead-light">
                        <tr><th>Môn</th><th class="text-right">Tổng</th><th class="text-right">Đạt</th><th class="text-right">Rớt</th></tr>
                    </thead>
                    <tbody>
                        @foreach (['lt' => 'LT', 'mp' => 'MP', 'h' => 'Hình', 'cc' => 'CC', 'duong' => 'Đường'] as $k => $n)
                            @php $c = $g->$k; @endphp
                            <tr>
                                <td>{{ $n }}</td>
                                <td class="text-right">{{ $c->tong }}</td>
                                <td class="text-right">{{ $c->dat }}</td>
                                <td class="text-right">{{ $c->rot }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mb-2">
            @if ($ghiChuDem !== [])
                <div class="col-md-6 mb-2">
                    <strong class="d-block mb-1">Phân bố Ghi chú (DS SH · H)</strong>
                    <ul class="list-unstyled mb-0 pl-0">
                        @foreach ($ghiChuDem as $gc => $sl)
                            <li><code>{{ $gc }}</code> × {{ $sl }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="col-md-6 mb-2">
                <strong class="d-block mb-1">Tóm tắt trạng thái</strong>
                <div>Đạt chung: <strong class="text-success">{{ count($dat) }}</strong></div>
                <div>Không đạt (có mặt): <strong class="text-danger">{{ count($kd) }}</strong></div>
                <div>Vắng kỳ thi: <strong class="text-warning">{{ count($vang) }}</strong></div>
                @if (count($vang) > 0)
                    <div class="text-muted mt-1" style="font-size: .75rem;">
                        <span class="text-dark">Vắng:</span>
                        @foreach ($vang as $t)
                            STT {{ $t->stt }} · {{ $t->hoTen }}<br>
                        @endforeach
                    </div>
                @endif
                @if (count($kd) > 0)
                    <div class="text-muted mt-1" style="font-size: .75rem;">
                        <span class="text-dark">KĐ:</span>
                        @foreach ($kd as $t)
                            STT {{ $t->stt }} · {{ $t->hoTen }}
                            @if ($t->h === 'KĐ')
                                (rớt H)
                            @endif
                            <br>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-panel mb-3">
    <div class="card-header py-2">Bảng đối chiếu từng thí sinh</div>
    <div class="card-body p-0">
        <div class="table-responsive doi-chieu-bang" style="max-height: 28rem; overflow: auto;">
            <table class="table table-sm table-bordered table-hover mb-0" style="font-size: .76rem;">
                <thead class="thead-light text-nowrap" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th title="DS SH A">STT</th>
                        <th title="DS SH B">SBD</th>
                        <th title="DS SH C">Họ tên</th>
                        <th title="DS SH D">CCCD</th>
                        <th title="DS SH E">NS</th>
                        <th title="DS SH G">Hạng</th>
                        <th title="DS SH H">Ghi chú</th>
                        <th title="Sheet1 G">Mặt</th>
                        <th>LT</th>
                        <th>MP</th>
                        <th>H</th>
                        <th>CC</th>
                        <th>Đ</th>
                        <th title="Sheet1 AA">KQ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($duLieu->thiSinh as $t)
                        @php
                            $rowClass = ! $t->coMat ? 'table-warning' : ($t->tongHop === 'KĐ' ? 'table-danger' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $t->stt }}</td>
                            <td>{{ $t->sbd ?: '—' }}</td>
                            <td class="text-nowrap">{{ $t->hoTen }}</td>
                            <td class="text-nowrap"><code>{{ $t->soDinhDanh }}</code></td>
                            <td class="text-nowrap">{{ $t->ngaySinh }}</td>
                            <td>{{ $t->hang }}</td>
                            <td><code>{{ $t->ghiChu ?: '—' }}</code></td>
                            <td class="text-center">{{ $t->coMat ? '1' : '0' }}</td>
                            @foreach (['lt', 'mp', 'h', 'cc', 'duong'] as $mon)
                                @php $kq = $t->{$mon}; @endphp
                                <td class="text-center {{ $kq === 'KĐ' ? 'text-danger font-weight-bold' : ($kq === 'Đ' ? 'text-success' : '') }}">
                                    {{ $kq !== '' ? $kq : '·' }}
                                </td>
                            @endforeach
                            <td class="text-center font-weight-bold {{ $t->tongHop === 'Đ' ? 'text-success' : 'text-danger' }}">{{ $t->tongHop ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer py-1 small text-muted">
        Vàng = vắng cả kỳ · Đỏ = có mặt nhưng KQ chung KĐ · «·» = không phải thi nội dung đó (theo Ghi chú).
    </div>
</div>

<style>
    .doi-chieu-meta dt { font-weight: 500; color: #6c757d; }
    .doi-chieu-bang th { background: #e9ecef !important; box-shadow: inset 0 -1px 0 #dee2e6; }
</style>