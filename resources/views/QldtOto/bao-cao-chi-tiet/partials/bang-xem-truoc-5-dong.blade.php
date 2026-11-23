@php
    $xemTruocGhep = $xemTruocGhep ?? [];
    $dong = $xemTruocGhep['dong'] ?? [];
@endphp
@if ($dong !== [])
    @if (! empty($xemTruocGhep['file_vua_tai']))
        <p class="small text-muted mb-2">
            <strong>File vừa áp dụng:</strong>
            {{ implode(' · ', $xemTruocGhep['file_vua_tai']) }}
        </p>
    @endif
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 text-center" style="font-size: 0.8rem;">
            <thead class="thead-light">
                <tr>
                    <th>STT</th>
                    <th class="text-left">Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>CCCD</th>
                    <th>Hạng</th>
                    <th>LT</th>
                    <th>MP</th>
                    <th>H</th>
                    <th>CC</th>
                    <th>ĐT</th>
                    <th>Tổng</th>
                    <th class="text-left">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dong as $ts)
                    <tr>
                        <td>{{ $ts['stt'] ?? '' }}</td>
                        <td class="text-left">{{ $ts['hoTen'] ?? '' }}</td>
                        <td>{{ $ts['ngaySinh'] ?? '' }}</td>
                        <td>{{ $ts['soDinhDanh'] ?? '' }}</td>
                        <td>{{ $ts['hang'] ?? '' }}</td>
                        <td>{{ $ts['lt'] ?? '' }}</td>
                        <td>{{ $ts['mp'] ?? '' }}</td>
                        <td>{{ $ts['h'] ?? '' }}</td>
                        <td>{{ $ts['cc'] ?? '' }}</td>
                        <td>{{ $ts['duong'] ?? '' }}</td>
                        <td><strong>{{ $ts['tongHop'] ?? '' }}</strong></td>
                        <td class="text-left small">{{ $ts['ghiChu'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif