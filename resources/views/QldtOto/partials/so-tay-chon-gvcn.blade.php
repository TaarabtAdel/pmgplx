<div class="form-row">
    <div class="form-group col-md-6">
        <label for="ma_gv">Giáo viên chủ nhiệm (GVCN) <span class="text-danger">*</span></label>
        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.giao-vien-select', [
            'name' => 'ma_gv',
            'selectedMa' => old('ma_gv', $form['ma_gv'] ?? ''),
            'giaoVienOptions' => $giaoVienOptions,
        ])
        @error('ma_gv')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>
