@php
    $items = $qldtOtoLichSuXuat['items'] ?? collect();
@endphp

<div class="card card-panel mt-3 mb-0 qldtoto-lich-su-xuat">
    <div class="card-header py-2">
        <strong>Lịch sử xuất</strong>
        <span class="text-muted font-weight-normal small">({{ $items->count() }} bản ghi gần nhất)</span>
    </div>
    <div class="card-body pt-2">
        @if ($items->isEmpty())
            <p class="text-muted small mb-0">Chưa có lần xuất nào được ghi nhận trên màn này.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:8.5rem;">Xuất lúc</th>
                            <th style="width:7rem;">Ngày VB</th>
                            <th style="width:10rem;">Số CV / QĐ</th>
                            <th style="width:6rem;">Mã khoá</th>
                            <th style="width:3.5rem;">Loại</th>
                            <th>Tên file</th>
                            <th style="width:7rem;">Người xuất</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $row)
                            <tr>
                                <td class="text-nowrap small">{{ $row->ThoiGianXuat?->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap small">{{ $row->NgayVanBan?->format('d/m/Y') ?? '—' }}</td>
                                <td class="small">{{ $row->SoCongVan ?: '—' }}</td>
                                <td class="small text-monospace">{{ $row->MaKh ?: '—' }}</td>
                                <td class="small">{{ $row->LoaiFile ? strtoupper($row->LoaiFile) : '—' }}</td>
                                <td class="small text-break">
                                    {{ $row->TenChucNang ?: '—' }}
                                    @if ($row->TenFile)
                                        <br><span class="text-muted">{{ $row->TenFile }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $row->nguoiXuat?->Ten ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>