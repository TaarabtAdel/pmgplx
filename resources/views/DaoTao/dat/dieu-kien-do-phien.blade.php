@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Điều kiện dò phiên DAT')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Điều kiện dò phiên DAT</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Dò phiên với lịch xe
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện đạt
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">
                    ← Quản lý phiên
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Cấu hình dùng khi <strong>dò phiên với lịch xe tập</strong> và kiểm tra lịch xe tại Quản lý phiên.
                    Ghép phiên theo mã khóa, giáo viên, biển số, ngày; sau đó so TG phiên với khung lịch đã chọn.
                </p>
                <p class="mb-0">
                    <strong>Cho phép sớm / muộn</strong> mở rộng biên hợp lệ so với TG bắt đầu và kết thúc lịch
                    (mặc định 0 = khớp chính xác theo phút).
                </p>
            </div>

            <form method="POST" action="{{ route('daotao.pdt.dat.dieu-kien-do-phien.update') }}" class="mb-4">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Cho phép sớm (phút)</label>
                        <input type="number" name="cho_phep_som_phut" class="form-control"
                               min="0" max="999" required
                               value="{{ old('cho_phep_som_phut', $cauHinh->ChoPhepSomPhut) }}">
                        <small class="form-text text-muted">
                            Phiên được bắt đầu sớm hơn TG bắt đầu lịch tối đa bao nhiêu phút vẫn coi là hợp lệ.
                            Ví dụ: lịch 07:30, cho phép sớm 2 phút → phiên từ 07:28 vẫn hợp lệ.
                        </small>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Cho phép muộn (phút)</label>
                        <input type="number" name="cho_phep_muon_phut" class="form-control"
                               min="0" max="999" required
                               value="{{ old('cho_phep_muon_phut', $cauHinh->ChoPhepMuonPhut) }}">
                        <small class="form-text text-muted">
                            Phiên được kết thúc muộn hơn TG kết thúc lịch tối đa bao nhiêu phút vẫn coi là hợp lệ.
                            Ví dụ: lịch 17:59, cho phép muộn 1 phút → phiên đến 18:00 vẫn hợp lệ.
                        </small>
                    </div>
                </div>

                @if ($cauHinh->NgayCapNhat)
                    <p class="small text-muted mb-3">
                        Cập nhật lần cuối: {{ $cauHinh->NgayCapNhat->format('d/m/Y H:i') }}
                    </p>
                @endif

                <button type="submit" class="btn btn-navy">Lưu điều kiện</button>
            </form>
        </div>
    </div>
@endsection
