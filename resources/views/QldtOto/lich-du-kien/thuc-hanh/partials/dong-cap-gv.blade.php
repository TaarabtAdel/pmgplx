@php
    $ten = fn (string $field): string => $i === null ? '' : 'name="cap_gv['.$i.']['.$field.']"';
@endphp
<tr class="dong-cap-gv">
    <td class="col-stt so-cap">{{ $i === null ? '' : $i + 1 }}</td>
    @foreach (['gv_sang' => 'Chọn giáo viên sáng…', 'gv_chieu' => 'Chọn giáo viên chiều…'] as $field => $placeholder)
        <td>
            <select class="form-control form-control-sm chon-s2 chon-gv" data-field="{{ $field }}" {!! $ten($field) !!} data-placeholder="{{ $placeholder }}">
                <option value=""></option>
                @foreach ($gvOptions as $gv)
                    <option value="{{ $gv['ma'] }}" data-xe="{{ $gv['xe'] }}" @selected(($c[$field] ?? '') === $gv['ma'])>{{ $gv['nhan'] }}</option>
                @endforeach
            </select>
        </td>
    @endforeach
    <td>
        <select class="form-control form-control-sm chon-s2" data-field="xe" {!! $ten('xe') !!} data-placeholder="Chọn xe…">
            <option value=""></option>
            @foreach (collect($xeOptions)->groupBy('hang') as $hangXe => $dsXe)
                <optgroup label="Hạng {{ $hangXe ?: '—' }}">
                    @foreach ($dsXe as $x)
                        <option value="{{ $x['bien_so'] }}" @selected(($c['xe'] ?? '') === $x['bien_so'])>{{ $x['nhan'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-xoa-cap" title="Xoá cặp">×</button>
    </td>
</tr>
