<div class="form-row">
    <div class="form-group col-md-4">
        <label for="so_quyet_dinh">Quyết định thành lập lớp (mục I)</label>
        <div class="input-group input-group-sm">
            <input type="text" class="form-control @error('so_quyet_dinh') is-invalid @enderror"
                   id="so_quyet_dinh" name="so_quyet_dinh" inputmode="numeric"
                   value="{{ old('so_quyet_dinh', $form['so_quyet_dinh'] ?? '') }}"
                   placeholder="vd. 431" maxlength="10">
            <div class="input-group-append">
                <span class="input-group-text">{{ \App\Support\QldtOto\QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD }}</span>
            </div>
        </div>
        @error('so_quyet_dinh')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="ngay_quyet_dinh">Ngày trên quyết định</label>
        <input type="date" class="form-control form-control-sm @error('ngay_quyet_dinh') is-invalid @enderror"
               id="ngay_quyet_dinh" name="ngay_quyet_dinh"
               value="{{ old('ngay_quyet_dinh', $form['ngay_quyet_dinh'] ?? '') }}">
        @error('ngay_quyet_dinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('so_quyet_dinh')?.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });
</script>
@endpush
