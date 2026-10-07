@php
    $tu = $testOne['update'] ?? [];
    $fr = $testOne['file_row'] ?? [];
    $ex = $testOne['explain'] ?? [];
@endphp
<div class="border rounded p-3 mb-3 bg-light small">
    <p class="font-weight-bold mb-2">Kết quả thử — mã <code>{{ $tu['ma_hoc_vien'] ?? $testMa ?? '' }}</code></p>
    <div class="mb-1">
        <strong>Dòng Excel {{ $fr['excel_row'] ?? '—' }}</strong>
        — <code>{{ $tu['ma_hoc_vien'] ?? '' }}</code>
        {{ $tu['ho_ten'] ?? '' }}
        · Hạng <strong>{{ $tu['hang_gplx'] ?? '—' }}</strong>
        · Nhóm <strong>{{ $tu['nhom_label'] ?? '' }}</strong>
        · Kết luận:
        @if ((int) ($tu['payload']['KetLuanCSDT'] ?? 0) === 1)
            <span class="badge badge-success">Đạt</span>
        @else
            <span class="badge badge-danger">Không đạt</span>
        @endif
        @if (! empty($tu['thieu_dat']))
            <span class="text-warning">(Chưa có giờ/km DAT)</span>
        @endif
    </div>
    @if (! empty($ex['chi_tiet']))
        <table class="table table-sm table-bordered bg-white mb-2">
            <thead class="thead-light">
                <tr>
                    <th>Điều kiện</th>
                    <th>Giá trị</th>
                    <th>Ngưỡng ≥</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ex['chi_tiet'] as $d)
                    <tr @class(['table-success' => $d['ok'], 'table-danger' => ! $d['ok']])>
                        <td>{{ $d['label'] }}</td>
                        <td>{{ $formatVal('_num', $d['value']) }}</td>
                        <td>
                            @if (! empty($d['min_exclusive']))
                                &gt; {{ $formatVal('_num', $d['min']) }}
                            @else
                                ≥ {{ $formatVal('_num', $d['min']) }}
                            @endif
                        </td>
                        <td>{{ $d['ok'] ? 'Đạt' : 'Thiếu' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif (($tu['nhom'] ?? '') === \App\Support\DaoTao\KetQuaDaoTaoUpdater::NHOM_KHAC)
        <p class="text-muted mb-2">Hạng GPLX không thuộc B sàn / B tự động / C1 → Kết luận CSDT = Không đạt.</p>
    @endif
    <p class="font-weight-bold mb-1">Giá trị trên hồ sơ (Mới / Hiện tại trước lưu)</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered bg-white mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Trường</th>
                    <th>Mới</th>
                    <th>Hiện tại</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Support\DaoTao\KetQuaDaoTaoUpdater::UPDATE_FIELDS as $field)
                    @php
                        $moi = $tu['payload'][$field] ?? null;
                        $cu = $tu['hien_tai'][$field] ?? null;
                        $doi = (string) $moi !== (string) $cu;
                    @endphp
                    <tr @class(['font-weight-bold' => $doi])>
                        <td>{{ $fieldLabels[$field] ?? $field }}</td>
                        <td>{{ $formatVal($field, $moi) }}</td>
                        <td>{{ $formatVal($field, $cu) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
