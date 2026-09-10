@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Điều kiện đạt DAT')

@section('content')
    @php
        $apDungTuNgayFormatted = \Carbon\Carbon::parse($apDungTuNgay)->format('d/m/Y');
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Điều kiện đạt DAT</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.dieu-kien-canh-bao') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện cảnh báo
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-do-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện dò phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Ngưỡng tối thiểu theo <strong>hạng GPLX</strong> để xét đạt chương trình thực hành DAT
                    (tập lái ban đêm, xe số tự động, số giờ học, tổng quãng đường).
                </p>
                <p class="mb-0 font-weight-bold">
                    Áp dụng từ ngày {{ $apDungTuNgayFormatted }}
                </p>
            </div>

            <form method="POST" action="{{ route('daotao.pdt.dat.dieu-kien-dat.store') }}"
                  class="mb-4 border rounded p-3 bg-white">
                @csrf
                <strong class="small d-block mb-2">Thêm điều kiện đạt</strong>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2 mb-md-0">
                        <label class="small mb-1">Hạng</label>
                        <input type="text" name="hang" class="form-control form-control-sm" maxlength="20"
                               placeholder="VD: B, B.01, C1" required>
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label class="small mb-1">Tập lái ban đêm (giờ)</label>
                        <input type="number" name="tap_lai_ban_dem_gio" class="form-control form-control-sm"
                               min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label class="small mb-1">Xe số tự động (giờ)</label>
                        <input type="number" name="xe_so_tu_dong_gio" class="form-control form-control-sm"
                               min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label class="small mb-1">Số giờ học</label>
                        <input type="number" name="so_gio_hoc" class="form-control form-control-sm"
                               min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group col-md-3 mb-md-0">
                        <label class="small mb-1">Tổng quãng đường (km)</label>
                        <input type="number" name="tong_quang_duong_km" class="form-control form-control-sm"
                               min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label class="small mb-1">TT</label>
                        <input type="number" name="thu_tu" class="form-control form-control-sm" min="0" value="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-navy mt-2">Thêm</button>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="70">TT</th>
                            <th width="90">Hạng</th>
                            <th>Tập lái ban đêm (giờ)</th>
                            <th>Xe số tự động (giờ)</th>
                            <th>Số giờ học</th>
                            <th>Tổng quãng đường (km)</th>
                            <th width="140">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td colspan="7" class="p-0">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td width="70" class="border-0">
                                                <form method="POST" action="{{ route('daotao.pdt.dat.dieu-kien-dat.update', $item->Id) }}"
                                                      id="frm-dk-dat-update-{{ $item->Id }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" name="thu_tu" class="form-control form-control-sm"
                                                           min="0" value="{{ $item->ThuTu ?? 0 }}"
                                                           form="frm-dk-dat-update-{{ $item->Id }}">
                                                </form>
                                            </td>
                                            <td width="90" class="border-0">
                                                <input type="text" name="hang" class="form-control form-control-sm"
                                                       maxlength="20" value="{{ $item->Hang }}" required
                                                       form="frm-dk-dat-update-{{ $item->Id }}">
                                            </td>
                                            <td class="border-0">
                                                <input type="number" name="tap_lai_ban_dem_gio" class="form-control form-control-sm"
                                                       min="0" step="0.01" value="{{ $item->TapLaiBanDemGio }}" required
                                                       form="frm-dk-dat-update-{{ $item->Id }}">
                                            </td>
                                            <td class="border-0">
                                                <input type="number" name="xe_so_tu_dong_gio" class="form-control form-control-sm"
                                                       min="0" step="0.01" value="{{ $item->XeSoTuDongGio }}" required
                                                       form="frm-dk-dat-update-{{ $item->Id }}">
                                            </td>
                                            <td class="border-0">
                                                <input type="number" name="so_gio_hoc" class="form-control form-control-sm"
                                                       min="0" step="0.01" value="{{ $item->SoGioHoc }}" required
                                                       form="frm-dk-dat-update-{{ $item->Id }}">
                                            </td>
                                            <td class="border-0">
                                                <input type="number" name="tong_quang_duong_km" class="form-control form-control-sm"
                                                       min="0" step="0.01" value="{{ $item->TongQuangDuongKm }}" required
                                                       form="frm-dk-dat-update-{{ $item->Id }}">
                                            </td>
                                            <td width="140" class="border-0 align-middle text-nowrap">
                                                <button type="submit" class="btn btn-sm btn-outline-primary"
                                                        form="frm-dk-dat-update-{{ $item->Id }}">Lưu</button>
                                                <form method="POST" action="{{ route('daotao.pdt.dat.dieu-kien-dat.destroy', $item->Id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Xóa điều kiện đạt hạng «{{ $item->Hang }}»?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Chưa có điều kiện đạt. Thêm ở form phía trên.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
