@php
    $exportRoute = $exportRoute ?? '';
    $filters = $filters ?? [];
    $exportColumns = $exportColumns ?? [];
    $total = (int) ($total ?? 0);
    $formId = $formId ?? 'formXuatExcelHv';
    $modalId = $modalId ?? 'modalXuatExcelHv';
    $cotKhacTieuDe = $cotKhacTieuDe ?? 'Cột DB khác';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ $exportRoute }}" target="_blank" id="{{ $formId }}">
                @csrf
                <input type="hidden" name="tu_khoa" value="{{ $filters['tu_khoa'] ?? '' }}">
                <input type="hidden" name="ma_kh" value="{{ $filters['ma_kh'] ?? '' }}">
                <input type="hidden" name="sale" value="{{ $filters['sale'] ?? '' }}">
                <input type="hidden" name="hang_gplx" value="{{ $filters['hang_gplx'] ?? '' }}">
                <input type="hidden" name="trang_thai" value="{{ $filters['trang_thai'] ?? '' }}">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="{{ $modalId }}Label">Xuất Excel — chọn cột</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        Xuất theo bộ lọc hiện tại ({{ number_format($total) }} học viên). Tick các cột cần có trong file.
                    </p>
                    <div class="d-flex flex-wrap mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary mr-1 mb-1 hv-export-chon-uu-tien" data-form="#{{ $formId }}">Chỉ cột ưu tiên mặc định</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 hv-export-chon-tat" data-form="#{{ $formId }}">Chọn tất cả</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1 hv-export-bo-chon" data-form="#{{ $formId }}">Bỏ chọn</button>
                    </div>
                    @php
                        $cotUuTien = array_filter($exportColumns, fn ($c) => $c['uu_tien']);
                        $cotKhac = array_filter($exportColumns, fn ($c) => ! $c['uu_tien']);
                    @endphp
                    <h6 class="font-weight-bold text-primary mb-2">Cột ưu tiên</h6>
                    <div class="row">
                        @foreach ($cotUuTien as $col)
                            <div class="col-md-4 col-sm-6 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input hv-export-col hv-export-col-uu-tien"
                                           name="cols[]" value="{{ $col['key'] }}" id="{{ $formId }}-col-{{ $col['key'] }}"
                                           data-mac-dinh="{{ $col['mac_dinh'] ? '1' : '0' }}"
                                           @checked($col['mac_dinh'])>
                                    <label class="custom-control-label" for="{{ $formId }}-col-{{ $col['key'] }}">{{ $col['label'] }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($cotKhac !== [])
                        <h6 class="font-weight-bold text-secondary mt-3 mb-2">{{ $cotKhacTieuDe }}</h6>
                        <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                            <div class="row">
                                @foreach ($cotKhac as $col)
                                    <div class="col-md-6 mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input hv-export-col"
                                                   name="cols[]" value="{{ $col['key'] }}" id="{{ $formId }}-col-{{ $col['key'] }}">
                                            <label class="custom-control-label small" for="{{ $formId }}-col-{{ $col['key'] }}">{{ $col['label'] }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-success">⬇ Tải Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>
