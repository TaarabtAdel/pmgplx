@extends('layouts.quan-ly')

@section('title', 'Tạo lịch dạy lý thuyết')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Tạo lịch dạy lý thuyết</span>
            <a href="{{ route('lich.gv.index') }}" class="btn btn-sm btn-success">← Danh sách</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('lich.ly-thuyet.store') }}">
                @csrf

                <h5 class="section-title">Thông tin giảng dạy</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
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
                        <label for="ma_gv">Giáo viên</label>
                        <select name="ma_gv[]" id="ma_gv" class="form-control" multiple required>
                            @foreach ($giaoViens as $gv)
                                <option value="{{ $gv->MaGV }}" @selected(in_array($gv->MaGV, $selectedGiaoViens, true))>
                                    {{ $gv->TenGV }} ({{ $gv->MaGV }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="ten_mon_hoc">Môn học</label>
                        <select name="ten_mon_hoc" id="ten_mon_hoc" class="form-control" required>
                            <option value="">-- Chọn môn học --</option>
                            @foreach ($monHocs as $mh)
                                <option value="{{ $mh->TenMH }}" @selected(old('ten_mon_hoc') === $mh->TenMH)>
                                    {{ $mh->TenMH }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="dia_diem">Địa điểm</label>
                        <input type="text" class="form-control" name="dia_diem" id="dia_diem" value="{{ $diaDiem }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giờ bắt đầu</label>
                        @include('lich._gio-phut', ['name' => 'gio_bd', 'value' => $gioBD])
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giờ kết thúc</label>
                        @include('lich._gio-phut', ['name' => 'gio_kt', 'value' => $gioKT])
                    </div>
                </div>

                <h5 class="section-title">Chọn ngày dạy</h5>
                <div class="calendar">
                    {!! $calendarHtml !!}
                </div>

                <p class="font-weight-bold mb-3">
                    <span id="lblSoNgay">Đã chọn: 0 ngày</span>
                </p>

                <input type="hidden" name="ngay_chon" id="ngay_chon" value="{{ old('ngay_chon') }}">

                <button type="submit" class="btn btn-navy btn-lg">Lưu lịch dạy</button>
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

    $('#ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: false,
        width: '100%',
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; },
            searching: function () { return 'Đang tìm...'; }
        }
    }).on('change', function () {
        window.location = '{{ route('lich.ly-thuyet.create') }}?ma_kh=' + encodeURIComponent(this.value);
    });

    $('#ma_gv').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm và chọn giáo viên...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; },
            searching: function () { return 'Đang tìm...'; },
            removeAllItems: function () { return 'Xóa tất cả'; }
        }
    });
</script>
@endpush
