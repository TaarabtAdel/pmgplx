@extends('layouts.quan-ly')

@section('title', 'Tạo lịch xe tập lái hàng loạt')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Tạo lịch xe tập lái hàng loạt</span>
            <a href="{{ route('lich.xe.index') }}" class="btn btn-sm btn-success">← Danh sách</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('lich.thuc-hanh.store') }}">
                @csrf

                <h5 class="section-title">Thông tin khóa học</h5>
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
                        <select name="ma_gv" id="ma_gv" class="form-control" required>
                            <option value="">-- Chọn giáo viên --</option>
                            @foreach ($giaoViens as $gv)
                                <option value="{{ $gv->MaGV }}" @selected(old('ma_gv') === $gv->MaGV)>
                                    {{ $gv->TenGV }} ({{ $gv->MaGV }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="bien_so_xe">Xe tập</label>
                        <select name="bien_so_xe" id="bien_so_xe" class="form-control" required>
                            <option value="">-- Chọn xe --</option>
                            @foreach ($xeTaps as $xe)
                                <option value="{{ $xe }}" @selected(old('bien_so_xe') === $xe)>
                                    {{ $xe }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="dia_diem">Địa điểm</label>
                        <input type="text" class="form-control" name="dia_diem" id="dia_diem" value="{{ $diaDiem }}" required>
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

                <h5 class="section-title">Chọn ngày tập</h5>
                <div class="calendar">
                    {!! $calendarHtml !!}
                </div>

                <p class="font-weight-bold mb-3">
                    <span id="lblSoNgay">Đã chọn: 0 ngày</span>
                </p>

                <input type="hidden" name="ngay_chon" id="ngay_chon" value="{{ old('ngay_chon') }}">

                <button type="submit" class="btn btn-navy btn-lg">Lưu lịch</button>
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
        width: '100%'
    }).on('change', function () {
        window.location = '{{ route('lich.thuc-hanh.create') }}?ma_kh=' + encodeURIComponent(this.value);
    });

    $('#ma_gv, #bien_so_xe').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
</script>
@endpush
