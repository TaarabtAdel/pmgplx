@php
    $gvList = $block['giao_vien'] ?? [];
    if ($gvList === []) {
        $gvList = [['ma_gv' => '', 'ho_ten' => '', 'tu_ngay' => '', 'den_ngay' => '', 'noi_dung' => 'GVTH']];
    }
@endphp
<div class="card border mb-3 xe-block" data-xe-index="{{ $xi }}">
    <div class="card-header py-2 d-flex align-items-center">
        <span class="font-weight-bold mr-2">Xe <span class="xe-stt">{{ (int) $xi + 1 }}</span></span>
        <div class="flex-grow-1" style="max-width: 14rem;">
            @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.xe-select', [
                'name' => 'xe_blocks['.$xi.'][bien_so]',
                'selectedBienSo' => $block['bien_so'] ?? '',
            ])
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger ml-auto btn-xoa-xe" title="Xóa xe">✕ Xóa xe</button>
    </div>
    <div class="card-body p-2">
        <div class="form-row mb-2">
            <div class="col-md-3">
                <label class="small text-muted mb-1">Số quyết định</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control inp-so-qd" inputmode="numeric" pattern="\d*"
                           name="xe_blocks[{{ $xi }}][so_quyet_dinh]"
                           value="{{ old('xe_blocks.'.$xi.'.so_quyet_dinh', $block['so_quyet_dinh'] ?? '') }}"
                           placeholder="vd. 640" maxlength="10">
                    <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1">Loại xe</label>
                <input type="text" class="form-control form-control-sm inp-loai-xe" name="xe_blocks[{{ $xi }}][loai_xe]"
                       value="{{ old('xe_blocks.'.$xi.'.loai_xe', $block['loai_xe'] ?? '') }}" maxlength="60">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1">Nhãn hiệu xe</label>
                <input type="text" class="form-control form-control-sm inp-nhan-hieu" name="xe_blocks[{{ $xi }}][nhan_hieu]"
                       value="{{ old('xe_blocks.'.$xi.'.nhan_hieu', $block['nhan_hieu'] ?? '') }}" maxlength="60">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1">Màu sơn</label>
                <input type="text" class="form-control form-control-sm inp-mau-son" name="xe_blocks[{{ $xi }}][mau_son]"
                       value="{{ old('xe_blocks.'.$xi.'.mau_son', $block['mau_son'] ?? '') }}" maxlength="60">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-2">
                <thead class="thead-light">
                    <tr>
                        <th style="width:11.5rem;">Giáo viên</th>
                        <th style="width:9rem;">Từ ngày</th>
                        <th style="width:9rem;">Đến ngày</th>
                        <th style="width:10rem;">Nội dung</th>
                        <th style="width:2.5rem;"></th>
                    </tr>
                </thead>
                <tbody class="gv-body">
                    @foreach ($gvList as $gi => $gv)
                        @include('QldtOto.quyet-dinh-dieu-dong-xe.partials.gv-row', [
                            'xi' => $xi,
                            'gi' => $gi,
                            'gv' => $gv,
                            'noiDungOptions' => $noiDungOptions ?? null,
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary btn-them-gv">＋ Thêm giáo viên</button>
    </div>
</div>
