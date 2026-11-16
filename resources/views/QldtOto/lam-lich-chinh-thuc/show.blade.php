@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Lịch chính thức: '.$lich->TenKhoa)

@section('content')
    @include('QldtOto.lam-lich-chinh-thuc.partials.breadcrumb', ['trangCon' => $lich->TenKhoa])

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif
    @foreach ($errors->all() as $err)
        <div class="alert alert-danger py-2" style="white-space: pre-line;">{{ $err }}</div>
    @endforeach

    <div class="card card-panel mb-3">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <span class="mr-auto">{{ $lich->TenKhoa }} · {{ $hangOptions[$lich->Hang] ?? $lich->Hang }}</span>
            <a href="{{ route('qldtoto.lam-lich-chinh-thuc.index') }}" class="btn btn-sm btn-outline-secondary">← Danh sách</a>
            @if ($lich->LichDuKienId)
                <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.edit', $lich->LichDuKienId) }}" class="btn btn-sm btn-outline-secondary">Nguồn dự kiến</a>
            @endif
        </div>
        <div class="card-body">
            <div class="form-row small text-muted mb-2">
                <div class="col-md-3">BĐ TH: <strong class="text-dark">{{ $lich->NgayBatDauThucHanh?->format('d/m/Y') }}</strong></div>
                <div class="col-md-3">Xe tự động: <strong class="text-dark">{{ $lich->XeTuDong ?: '—' }}</strong></div>
                <div class="col-md-3">
                    Lưu PM lần cuối:
                    <strong class="text-dark">{{ $lich->NgayLuuPm?->format('d/m/Y H:i') ?? 'Chưa' }}</strong>
                    @if ($lich->SoBuoiLuuPm)
                        <span>({{ $lich->SoBuoiLuuPm }} buổi)</span>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('qldtoto.lam-lich-chinh-thuc.update-ma-kh', $lich) }}" class="form-row align-items-end mb-0">
                @csrf
                @method('PUT')
                <div class="form-group col-md-5 mb-0">
                    <label for="ma_kh">Mã khoá PMGPLX (khi lưu vào phần mềm)</label>
                    <select class="form-control form-control-sm sel-khoa-pmgplx" id="ma_kh" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected(old('ma_kh', $lich->MaKH) === $kh->MaKH)>
                                {{ $kh->TenKH }} ({{ $kh->MaKH }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-outline-primary btn-block">Lưu mã khoá</button>
                </div>
            </form>
        </div>
    </div>

    @include('QldtOto.lam-lich-chinh-thuc.partials.bang-lich', [
        'lich' => $lich,
        'bangLich' => $bangLich,
        'coKeoThuTu' => true,
    ])

    <div class="card card-panel mt-3">
        <div class="card-header">Xuất Excel &amp; lưu vào phần mềm</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('qldtoto.lam-lich-chinh-thuc.export-excel', $lich) }}" class="btn btn-sm btn-outline-success">
                    Xuất Excel (mẫu nhập lịch)
                </a>
                <form method="POST" action="{{ route('qldtoto.lam-lich-chinh-thuc.luu-vao-phan-mem', $lich) }}" class="d-inline"
                      onsubmit="return confirm('Lưu lịch vào PMGPLX cho khoá {{ $lich->MaKH ?: '…' }}?\nLịch thực hành (IsKhoaHoc*=0) hiện có của khoá sẽ bị xoá rồi ghi lại.');">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $lich->MaKH }}">
                    <button type="submit" class="btn btn-sm btn-navy" @disabled(! $lich->MaKH)>
                        Lưu vào phần mềm
                    </button>
                </form>
                @unless ($lich->MaKH)
                    <span class="small text-danger">Chọn và lưu mã khoá PMGPLX ở trên trước.</span>
                @endunless
            </div>
            <p class="small text-muted mt-2 mb-0">
                «Lưu vào phần mềm» ghi vào <code>KhoaHoc_GiaoVien</code> / <code>KhoaHoc_XeTap</code>
                (cùng kiểu nhập từ file Excel tại /pmgplx/lich/nhap-tu-file).
            </p>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
(function ($) {
    var $kh = $('#ma_kh');
    if ($kh.length) {
        $kh.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: $kh.data('placeholder') || '—'
        });
    }

    var holder = document.getElementById('thu-tu-bai-an');
    if (!holder || !window.Sortable) return;

    function capNhatInputAn() {
        holder.innerHTML = '';
        var theoCot = {};
        document.querySelectorAll('#lich-chinh-thuc tbody tr').forEach(function (tr) {
            if (tr.querySelector('.chu-nghi')) return;
            tr.querySelectorAll('.o-buoi-slot').forEach(function (ul) {
                var cot = ul.getAttribute('data-cot');
                var li = ul.querySelector('.o-buoi-keo');
                if (!cot || !li) return;
                if (!theoCot[cot]) theoCot[cot] = [];
                var id = li.getAttribute('data-id');
                if (id) theoCot[cot].push(id);
            });
        });
        Object.keys(theoCot).forEach(function (cot) {
            theoCot[cot].forEach(function (id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'thu_tu[' + cot + '][]';
                inp.value = id;
                holder.appendChild(inp);
            });
        });
    }

    document.querySelectorAll('#lich-chinh-thuc .o-buoi-slot').forEach(function (ul) {
        var cot = ul.getAttribute('data-cot');
        if (!cot) return;
        Sortable.create(ul, {
            group: { name: 'cot-' + cot, pull: true, put: true },
            draggable: '.o-buoi-keo',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'dang-keo',
            onEnd: function (evt) {
                if (evt.to !== evt.from && evt.to.children.length > 1) {
                    Array.from(evt.to.children).forEach(function (el) {
                        if (el !== evt.item) {
                            evt.from.appendChild(el);
                        }
                    });
                }
                capNhatInputAn();
            }
        });
    });

    capNhatInputAn();
})(jQuery);
</script>
@endpush