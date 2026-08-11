@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Tạo lịch xe tập lái hàng loạt')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Tạo lịch xe tập lái hàng loạt</span>
            <a href="{{ route('pmgplx.lich.xe.index') }}" class="btn btn-sm btn-success">← Danh sách</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('pmgplx.lich.thuc-hanh.store') }}">
                @csrf

                <h5 class="section-title">Thông tin khóa học</h5>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="ma_kh">Khóa học</label>
                        <select name="ma_kh" id="ma_kh" class="form-control" required>
                            @forelse ($khoaHocs as $kh)
                                <option value="{{ $kh->MaKH }}" @selected($maKH === $kh->MaKH)>
                                    {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                </option>
                            @empty
                                <option value="">Không có khóa học</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="thang">Tháng</label>
                        <select name="thang" id="thang" class="form-control" required>
                            @foreach ($thangThi as $thang => $tenThang)
                                <option value="{{ $thang }}" @selected($selectedThang === $thang)>{{ $tenThang }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="ma_gv">Giáo viên</label>
                        <select name="ma_gv[]" id="ma_gv" class="select2-multi" multiple data-placeholder="Tìm và chọn giáo viên...">
                            @foreach ($giaoViens as $gv)
                                <option value="{{ $gv->MaGV }}" @selected(in_array($gv->MaGV, $selectedGiaoViens, true))>
                                    {{ $gv->TenGV }} ({{ $gv->MaGV }})
                                    @if (trim((string) ($gv->BienSoXe ?? '')) !== '')
                                        — xe {{ $gv->BienSoXe }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Có thể chọn nhiều giáo viên. Xe tập lấy từ gắn xe tại
                            <a href="{{ route('pmgplx.dm.giao-vien.index') }}" target="_blank">Quản lý giáo viên</a>
                            (có thể đổi lại ở màn xem trước).
                        </small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="dia_diem">Địa điểm</label>
                        <select name="dia_diem" id="dia_diem" class="form-control">
                            @foreach ($diaDiem as $dd)
                                <option value="{{ $dd }}" @selected(($selectedDiaDiem ?? '') === $dd)>{{ $dd }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giờ bắt đầu</label>
                        @include('PMGPLX.lich._gio-phut', ['name' => 'gio_bd', 'value' => $gioBD])
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giờ kết thúc</label>
                        @include('PMGPLX.lich._gio-phut', ['name' => 'gio_kt', 'value' => $gioKT])
                    </div>
                </div>

                <h5 class="section-title">Chọn ngày tập</h5>
                <div class="calendar">
                    <h5 class="section-title">Tháng {{ $selectedThang }}/{{ $selectedNam }}</h5>
                    <div class="day-calendar">
                        @for ($i = 1; $i <= $daysOfMonth; $i++)
                            @php
                                $iso = sprintf('%04d-%02d-%02d', $selectedNam, (int) $selectedThang, $i);
                            @endphp
                            <div class="day" onclick="chonNgay(this, '{{ $iso }}')">{{ $i }}</div>
                        @endfor
                    </div>
                </div>

                <p class="font-weight-bold mb-3">
                    <span id="lblSoNgay">Đã chọn: 0 ngày</span>
                </p>

                <input type="hidden" name="ngay_chon" id="ngay_chon" value="{{ $ngayChon ?? '' }}">

                <button type="submit" class="btn btn-navy btn-lg">Xem trước kết quả</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var ngayChon = [];

    function chonNgay(obj, date) {
        if (obj.classList.contains('selected')) {
            obj.classList.remove('selected');
            ngayChon = ngayChon.filter(function (x) { return x != date; });
        } else {
            obj.classList.add('selected');
            ngayChon.push(date);
        }
        document.getElementById('ngay_chon').value = ngayChon.join(',');
        document.getElementById('lblSoNgay').innerHTML = 'Đã chọn: ' + ngayChon.length + ' ngày';
    }

    (function restoreSelectedDays() {
        var raw = document.getElementById('ngay_chon').value || '';
        if (!raw) return;
        ngayChon = raw.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
        ngayChon.forEach(function (date) {
            var el = document.querySelector('.day[onclick*="' + date + '"]');
            if (el) el.classList.add('selected');
        });
        document.getElementById('lblSoNgay').innerHTML = 'Đã chọn: ' + ngayChon.length + ' ngày';
    })();

    document.querySelectorAll('[data-time-picker]').forEach(function (wrap) {
        var hidden = wrap.querySelector('input[type="hidden"]');
        var hour = wrap.querySelector('.time-hour');
        var minute = wrap.querySelector('.time-minute');
        function sync() {
            hidden.value = hour.value + ':' + minute.value;
        }
        hour.addEventListener('change', sync);
        minute.addEventListener('change', sync);
    });

    function reloadCreateForm() {
        var maKh = $('#ma_kh').val() || '';
        var thang = $('#thang').val() || '';
        var q = [];
        if (maKh) q.push('ma_kh=' + encodeURIComponent(maKh));
        if (thang) q.push('thang=' + encodeURIComponent(thang));
        window.location = '{{ route('pmgplx.lich.thuc-hanh.create') }}' + (q.length ? '?' + q.join('&') : '');
    }

    $('#ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: false,
        width: '100%',
        dropdownParent: $('#ma_kh').closest('.form-group')
    }).on('change', reloadCreateForm);

    $('#thang').select2({
        theme: 'bootstrap4',
        width: '100%',
        dropdownParent: $('#thang').closest('.form-group')
    }).on('change', reloadCreateForm);

    $('#ma_gv').select2({
        theme: 'default',
        placeholder: $('#ma_gv').data('placeholder') || 'Tìm và chọn giáo viên...',
        allowClear: true,
        width: '100%',
        closeOnSelect: false,
        dropdownParent: $('#ma_gv').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; },
            searching: function () { return 'Đang tìm...'; },
            removeAllItems: function () { return 'Xóa tất cả'; }
        }
    });

    $('form').on('submit', function (e) {
        if (!$('#ma_gv').val() || !$('#ma_gv').val().length) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một giáo viên.');
            $('#ma_gv').select2('open');
        }
    });
</script>
@endpush
