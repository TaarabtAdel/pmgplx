@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Mẫu <code>quyet-dinh-hoan-thanh-khoa-hoc.docx</code>: trang 1 quyết định, trang 2 danh sách học viên kèm theo.
                Chọn khoá chính (học viên CSĐT đạt được tích sẵn); thêm khoá khác nếu có học viên khoá trước / thi lại.
            </p>

            <form method="GET" action="{{ route('qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.index') }}" class="form-row align-items-end mb-3" id="form-chon-khoa">
                <div class="form-group col-md-5 mb-0">
                    <label for="ma_kh_loc">{{ $maKh === '' ? 'Khoá (PMGPLX)' : 'Khoá chính' }}</label>
                    <select class="form-control form-control-sm chon-khoa" id="ma_kh_loc" name="ma_kh">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
                @foreach ($khoaThem as $ma)
                    <input type="hidden" name="khoa_them[]" value="{{ $ma }}">
                @endforeach
                @if ($maKh !== '')
                    <div class="form-group col-md-5 mb-0">
                        <label for="khoa_them_moi">Thêm khoá khác (thi lại / khoá trước)</label>
                        <select class="form-control form-control-sm chon-khoa" id="khoa_them_moi" name="khoa_them[]">
                            <option value=""></option>
                            @foreach ($khoaOptions as $kh)
                                @continue($kh->MaKH === $maKh || in_array($kh->MaKH, $khoaThem, true))
                                <option value="{{ $kh->MaKH }}">{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">{{ $maKh === '' ? 'Chọn khoá' : 'Thêm khoá' }}</button>
                </div>
            </form>

            @if ($khoaList === [])
                <p class="text-muted mb-0">Chọn khoá để tải danh sách học viên.</p>
            @else
                @if (session('error'))
                    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $chonCu = old('chon');
                @endphp

                <form method="POST" action="{{ route('qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.export') }}" id="form-hoan-thanh">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_qd">Số quyết định</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_qd" name="so_qd" inputmode="numeric"
                                       value="{{ old('so_qd') }}" placeholder="vd. 743" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_quyet_dinh">Ngày quyết định</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                                   value="{{ old('ngay_quyet_dinh', $ngayMacDinh) }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sẽ in</label>
                            <div class="form-control form-control-sm bg-light text-truncate h-auto" id="xem-nhom" style="white-space: normal;">—</div>
                        </div>
                    </div>

                    @foreach ($khoaList as $k)
                        @php
                            $ma = $k['ma_kh'];
                            // Khoá chính chưa có kết luận CSĐT trên PMGPLX: tích sẵn cả khoá để bỏ bớt người không đạt.
                            $chuaCoKetLuan = $k['chinh'] && ! collect($k['hoc_vien'])->contains(fn ($hv) => $hv['dat']);
                        @endphp
                        <input type="hidden" name="khoa_list[]" value="{{ $ma }}">
                        <div class="card border mb-3 khoa-block" data-ma="{{ $ma }}">
                            <div class="card-header py-2 d-flex flex-wrap align-items-center">
                                <strong class="mr-2">{{ $k['chinh'] ? 'Khoá chính' : 'Khoá thêm' }}:</strong>
                                <span class="mr-3">{{ $k['ten_kh'] }} <small class="text-muted">({{ $ma }})</small></span>
                                <label class="small mb-0 mr-1">Hạng</label>
                                <input type="text" class="form-control form-control-sm mr-2 inp-hang" style="width: 8.5rem;"
                                       name="khoa[{{ $ma }}][hang]" value="{{ old('khoa.'.$ma.'.hang', $k['hang']) }}" required>
                                <label class="small mb-0 mr-1">Khóa</label>
                                <input type="text" class="form-control form-control-sm mr-2 inp-khoa-so" style="width: 4.5rem;"
                                       name="khoa[{{ $ma }}][khoa_so]" value="{{ old('khoa.'.$ma.'.khoa_so', $k['khoa_so']) }}" required>
                                <label class="small mb-0 mr-1">Cột «Khóa»</label>
                                <input type="text" class="form-control form-control-sm mr-2" style="width: 6rem;"
                                       name="khoa[{{ $ma }}][ten_ngan]" value="{{ old('khoa.'.$ma.'.ten_ngan', $k['ten_ngan']) }}" required>
                                <span class="small text-muted mr-auto">Đã chọn <strong class="dem-chon">0</strong>/{{ count($k['hoc_vien']) }}</span>
                                @unless ($k['chinh'])
                                    <a class="btn btn-sm btn-outline-danger py-0"
                                       href="{{ route('qldtoto.quyet-dinh-hoan-thanh-khoa-hoc.index', ['ma_kh' => $maKh, 'khoa_them' => array_values(array_diff($khoaThem, [$ma]))]) }}">✕ Bỏ khoá</a>
                                @endunless
                            </div>
                            <div class="card-body p-0">
                                @if ($chuaCoKetLuan)
                                    <div class="alert alert-warning rounded-0 mb-0 py-1 px-2 small">
                                        Khoá này chưa có kết luận CSĐT trên PMGPLX — đang tích tất cả học viên, bỏ tích người không đủ điều kiện hoàn thành.
                                    </div>
                                @endif
                                <div class="table-responsive" style="max-height: 24rem;">
                                    <table class="table table-sm table-bordered table-hover mb-0" style="table-layout: fixed; min-width: 900px;">
                                        <colgroup>
                                            <col style="width: 2.5rem;"><col style="width: 3rem;"><col style="width: 16rem;"><col style="width: 7rem;"><col style="width: 8.5rem;"><col><col style="width: 7.5rem;">
                                        </colgroup>
                                        <thead class="thead-light" style="position: sticky; top: 0;">
                                            <tr>
                                                <th class="text-center"><input type="checkbox" class="chon-tat-ca" title="Chọn tất cả"></th>
                                                <th>STT</th>
                                                <th>Họ và tên</th>
                                                <th>Ngày sinh</th>
                                                <th>Số CCCD</th>
                                                <th>Nơi cư trú</th>
                                                <th>CSĐT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($k['hoc_vien'] as $hv)
                                                @php
                                                    $checked = is_array($chonCu)
                                                        ? in_array($hv['ma_dk'], (array) ($chonCu[$ma] ?? []), true)
                                                        : ($k['chinh'] && ($hv['dat'] || $chuaCoKetLuan));
                                                @endphp
                                                <tr class="{{ $hv['dat'] || $chuaCoKetLuan ? '' : 'table-warning' }}">
                                                    <td class="text-center"><input type="checkbox" class="chk-hv" name="chon[{{ $ma }}][]" value="{{ $hv['ma_dk'] }}" @checked($checked)></td>
                                                    <td class="stt text-muted"></td>
                                                    <td>{{ $hv['ho_ten'] }}</td>
                                                    <td>{{ $hv['ngay_sinh'] }}</td>
                                                    <td>{{ $hv['so_cmnd'] }}</td>
                                                    <td class="text-truncate" title="{{ $hv['noi_cu_tru'] }}">{{ $hv['noi_cu_tru'] }}</td>
                                                    <td>
                                                        @if ($hv['dat'])
                                                            <span class="badge badge-success">Đạt</span>
                                                        @elseif ($chuaCoKetLuan)
                                                            <span class="badge badge-light border">Chưa có</span>
                                                        @else
                                                            <span class="badge badge-warning">Chưa đạt</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-sm btn-navy">Xuất Word</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        $('.chon-khoa').each(function () {
            const $s = $(this);
            $s.select2({
                theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn khoá —',
                dropdownParent: $s.closest('.form-group'),
                language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
            });
        });
        $('#ma_kh_loc').on('select2:select', function () {
            // Đổi khoá chính: bỏ các khoá thêm cũ.
            const f = document.getElementById('form-chon-khoa');
            f.querySelectorAll('input[name="khoa_them[]"]').forEach(function (el) { el.remove(); });
            $('#khoa_them_moi').val(null);
            f.submit();
        });
        $('#khoa_them_moi').on('select2:select', function () { this.form.submit(); });

        const form = document.getElementById('form-hoan-thanh');
        if (!form) return;

        function capNhat() {
            const nhom = [];
            form.querySelectorAll('.khoa-block').forEach(function (card) {
                let stt = 0;
                const rows = card.querySelectorAll('tbody tr');
                rows.forEach(function (tr) {
                    const c = tr.querySelector('.chk-hv').checked;
                    tr.querySelector('.stt').textContent = c ? String(++stt) : '';
                    tr.classList.toggle('text-muted', !c);
                });
                card.querySelector('.dem-chon').textContent = stt;
                const all = card.querySelector('.chon-tat-ca');
                all.checked = stt > 0 && stt === rows.length;
                all.indeterminate = stt > 0 && stt < rows.length;
                if (stt > 0) {
                    nhom.push(String(stt).padStart(2, '0') + ' học viên Hạng ' + card.querySelector('.inp-hang').value.trim()
                        + ' khóa ' + card.querySelector('.inp-khoa-so').value.trim());
                }
            });
            document.getElementById('xem-nhom').textContent = nhom.length
                ? '… cho ' + (nhom.length > 1 ? nhom.slice(0, -1).join(', ') + ' và ' + nhom[nhom.length - 1] : nhom[0])
                : '—';
        }

        form.addEventListener('change', function (e) {
            if (e.target.classList.contains('chon-tat-ca')) {
                e.target.closest('.khoa-block').querySelectorAll('.chk-hv').forEach(function (c) { c.checked = e.target.checked; });
            }
            capNhat();
        });
        form.addEventListener('input', function (e) {
            if (e.target.classList.contains('inp-hang') || e.target.classList.contains('inp-khoa-so')) capNhat();
        });
        document.getElementById('so_qd').addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); });
        capNhat();
    });
})(jQuery);
</script>
@endpush
