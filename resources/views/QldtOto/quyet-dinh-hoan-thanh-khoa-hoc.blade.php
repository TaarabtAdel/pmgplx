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
                Xuất ZIP: <code>quyet-dinh-hoan-thanh-khoa-hoc.docx</code> (chỉ quyết định, mẫu «08. Quyết định hoàn thành…»)
                và <code>kem-quyet-dinh-hoan-thanh-khoa-hoc.xlsx</code> (danh sách kèm theo). Bảng dưới xem trước đúng cột Excel.
                Chọn khoá chính (học viên đạt CSĐT tích sẵn); thêm khoá khác nếu có HV khoá trước / thi lại.
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
                            <label>Đoạn «cho … học viên …» trên QĐ</label>
                            <div class="form-control form-control-sm bg-light text-truncate h-auto" id="xem-nhom" style="white-space: normal;">—</div>
                        </div>
                    </div>

                    <div class="card border bg-light mb-3" id="preview-excel-header">
                        <div class="card-body py-2 small">
                            <p class="font-weight-bold text-center text-uppercase mb-1" id="xem-tieu-de-ds">—</p>
                            <p class="text-center font-italic mb-0 text-muted" id="xem-dong-kem-theo">—</p>
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
                                <div class="table-responsive" style="max-height: 28rem;">
                                    <table class="table table-sm table-bordered table-hover mb-0 table-excel-preview" style="table-layout: fixed; min-width: 1100px;">
                                        <colgroup>
                                            <col style="width: 2.25rem;"><col style="width: 2.5rem;"><col style="width: 9rem;"><col style="width: 5rem;"><col style="width: 6.5rem;"><col style="width: 8.5rem;"><col style="width: 11rem;"><col style="width: 6.5rem;"><col style="width: 4.5rem;">
                                        </colgroup>
                                        <thead class="thead-light" style="position: sticky; top: 0;">
                                            <tr>
                                                <th class="text-center" rowspan="2"><input type="checkbox" class="chon-tat-ca" title="Chọn tất cả"></th>
                                                <th class="text-center" rowspan="2">Số TT</th>
                                                <th class="text-center" colspan="2">Họ tên học viên</th>
                                                <th class="text-center" rowspan="2">Ngày sinh</th>
                                                <th class="text-center" rowspan="2">Số CCCD/CC</th>
                                                <th class="text-center" rowspan="2">Số GCN trên Giấy XN HT</th>
                                                <th class="text-center" rowspan="2">Số GCN PM GPLX cũ</th>
                                                <th class="text-center" rowspan="2">KHÓA</th>
                                            </tr>
                                            <tr>
                                                <th class="small text-muted">Họ và tên đệm</th>
                                                <th class="small text-muted">Tên</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($k['hoc_vien'] as $hv)
                                                @php
                                                    $checked = is_array($chonCu)
                                                        ? in_array($hv['ma_dk'], (array) ($chonCu[$ma] ?? []), true)
                                                        : ($k['chinh'] && ($hv['dat'] || $chuaCoKetLuan));
                                                @endphp
                                                <tr class="preview-hv-row {{ $hv['dat'] || $chuaCoKetLuan ? '' : 'table-warning' }}"
                                                    title="{{ (! $hv['dat'] && ! $chuaCoKetLuan) ? 'Chưa đạt CSĐT — kiểm tra trước khi in' : '' }}">
                                                    <td class="text-center"><input type="checkbox" class="chk-hv" name="chon[{{ $ma }}][]" value="{{ $hv['ma_dk'] }}" @checked($checked)></td>
                                                    <td class="stt text-center text-muted"></td>
                                                    <td>{{ $hv['ho_dem'] }}</td>
                                                    <td>{{ $hv['ten_rieng'] }}</td>
                                                    <td class="text-center">{{ $hv['ngay_sinh_fm'] }}</td>
                                                    <td class="text-center">{{ $hv['so_cmnd'] }}</td>
                                                    <td class="small">{{ $hv['so_giay_ht'] }}</td>
                                                    <td class="text-center">{{ $hv['so_ccn'] }}</td>
                                                    <td class="text-center">{{ $k['ten_ngan'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-sm btn-navy">Xuất ZIP (Word + Excel)</button>
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

        const hangSuffix = {!! json_encode(\App\Support\QldtOto\QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD) !!};

        function tieuDeExcel(hang, khoaSo) {
            const h = String(hang || '').toUpperCase().replace(/[()]/g, ' ').replace(/\s+/g, ' ').trim();
            const k = String(khoaSo || '').trim();
            return 'DANH SÁCH HỌC VIÊN HỌC LÁI XE Ô TÔ HẠNG ' + h + (k ? ' KHÓA ' + k : '');
        }

        function dongKemTheo(soQd, ngayIso, dsNhom) {
            if (!soQd || !ngayIso || !dsNhom) return '—';
            const d = new Date(ngayIso + 'T12:00:00');
            const ngay = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
            return '(Kèm theo Quyết định số: ' + soQd + hangSuffix + ' ngày ' + ngay
                + ' của Trung tâm GDNN Mạnh Linh về việc Hoàn thành lớp học Đào tạo lái xe & Cấp giấy xác nhận hoàn thành khóa đào tạo lái xe cho '
                + dsNhom + ')';
        }

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
            const dsNhom = nhom.length
                ? (nhom.length > 1 ? nhom.slice(0, -1).join(', ') + ' và ' + nhom[nhom.length - 1] : nhom[0])
                : '';
            document.getElementById('xem-nhom').textContent = dsNhom ? 'cho ' + dsNhom : '—';

            const khoaChinh = form.querySelector('.khoa-block');
            const hang0 = khoaChinh ? khoaChinh.querySelector('.inp-hang').value.trim() : '';
            const khoaSo0 = khoaChinh ? khoaChinh.querySelector('.inp-khoa-so').value.trim() : '';
            document.getElementById('xem-tieu-de-ds').textContent = tieuDeExcel(hang0, khoaSo0);
            const soQd = document.getElementById('so_qd').value.trim();
            const ngayQd = document.getElementById('ngay_quyet_dinh').value;
            document.getElementById('xem-dong-kem-theo').textContent = dongKemTheo(soQd, ngayQd, dsNhom);
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
        document.getElementById('so_qd').addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); capNhat(); });
        document.getElementById('ngay_quyet_dinh').addEventListener('change', capNhat);
        capNhat();
    });
})(jQuery);
</script>
@endpush
