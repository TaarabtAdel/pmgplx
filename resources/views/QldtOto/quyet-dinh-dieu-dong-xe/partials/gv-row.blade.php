<tr class="gv-row">
    <td>
        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.giao-vien-select', [
            'name' => 'xe_blocks['.$xi.'][giao_vien]['.$gi.'][ma_gv]',
            'selectedMa' => $gv['ma_gv'] ?? '',
            'selectedHoTen' => $gv['ho_ten'] ?? '',
        ])
    </td>
    <td><input type="date" class="form-control form-control-sm" name="xe_blocks[{{ $xi }}][giao_vien][{{ $gi }}][tu_ngay]" value="{{ $gv['tu_ngay'] ?? '' }}"></td>
    <td><input type="date" class="form-control form-control-sm" name="xe_blocks[{{ $xi }}][giao_vien][{{ $gi }}][den_ngay]" value="{{ $gv['den_ngay'] ?? '' }}"></td>
    <td>
        <select class="form-control form-control-sm sel-noi-dung chon-s2" name="xe_blocks[{{ $xi }}][giao_vien][{{ $gi }}][noi_dung]">
            @foreach ($noiDungOptions ?? \App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport::NOI_DUNG_OPTIONS as $opt)
                <option value="{{ $opt }}" @selected(($gv['noi_dung'] ?? '') === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
    </td>
    <td><button type="button" class="btn btn-sm btn-outline-danger py-0 btn-xoa-gv" title="Xóa">✕</button></td>
</tr>
