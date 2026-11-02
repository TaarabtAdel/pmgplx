@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Vai trò')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thêm vai trò</div>
        <div class="card-body">
            <form method="POST" action="{{ route('hethong.vai-tro.store') }}" autocomplete="off">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Tên vai trò <span class="text-danger">*</span></label>
                        <input type="text" name="ten" class="form-control form-control-sm" maxlength="100" required
                               value="{{ old('ten') }}" placeholder="vd. Kế Toán">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Mã <small class="text-muted">(tự tạo nếu để trống)</small></label>
                        <input type="text" name="ma" class="form-control form-control-sm" maxlength="50"
                               value="{{ old('ma') }}" placeholder="vd. ke_toan" pattern="[a-z0-9_]+">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Sao chép quyền từ</label>
                        <select name="sao_chep_tu" class="form-control form-control-sm">
                            <option value="">— Không sao chép (chưa có quyền) —</option>
                            @foreach ($items as $vt)
                                @continue($vt->LaHeThong)
                                <option value="{{ $vt->MaVaiTro }}" @selected(old('sao_chep_tu') === $vt->MaVaiTro)>{{ $vt->TenVaiTro }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-success btn-block">＋ Thêm vai trò</button>
                    </div>
                </div>
                <p class="small text-muted mb-0">
                    Thêm xong vào <a href="{{ route('hethong.phan-quyen.index') }}">Phân quyền</a> để tích chức năng cho vai trò,
                    rồi gán vai trò ở <a href="{{ route('hethong.nguoi-dung.index') }}">Người dùng</a>.
                </p>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách vai trò</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 6rem;">Thứ tự</th>
                            <th>Tên vai trò</th>
                            <th style="width: 10rem;">Mã</th>
                            <th class="text-center" style="width: 8rem;">Người dùng</th>
                            <th class="text-center" style="width: 8rem;">Chức năng</th>
                            <th style="width: 12rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $vt)
                            <tr>
                                <td colspan="2" class="p-1">
                                    <form method="POST" action="{{ route('hethong.vai-tro.update', $vt->Id) }}" class="form-inline flex-nowrap" id="sua-vt-{{ $vt->Id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="thu_tu" class="form-control form-control-sm mr-2" style="width: 5rem;"
                                               value="{{ $vt->ThuTu }}" min="0" max="9999" required>
                                        <input type="text" name="ten" class="form-control form-control-sm flex-grow-1" maxlength="100"
                                               value="{{ $vt->TenVaiTro }}" required>
                                    </form>
                                </td>
                                <td class="align-middle">
                                    <code>{{ $vt->MaVaiTro }}</code>
                                    @if ($vt->LaHeThong)
                                        <span class="badge badge-secondary">Hệ thống</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('hethong.nguoi-dung.index', ['vai_tro' => $vt->MaVaiTro]) }}">{{ (int) ($soNguoi[$vt->MaVaiTro] ?? 0) }}</a>
                                </td>
                                <td class="text-center align-middle">
                                    {{ $vt->LaHeThong ? 'Tất cả' : (int) ($soQuyen[$vt->MaVaiTro] ?? 0) }}
                                </td>
                                <td class="align-middle text-nowrap">
                                    <button type="submit" form="sua-vt-{{ $vt->Id }}" class="btn btn-sm btn-outline-primary py-0">Lưu</button>
                                    @unless ($vt->LaHeThong)
                                        <form method="POST" action="{{ route('hethong.vai-tro.destroy', $vt->Id) }}" class="d-inline"
                                              onsubmit="return confirm('Xoá vai trò «{{ $vt->TenVaiTro }}» và toàn bộ quyền của vai trò này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0">Xoá</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-2 mb-0">
                Admin là vai trò hệ thống: luôn đủ quyền, không xoá được. Chỉ xoá được vai trò khi không còn người dùng nào giữ vai trò đó.
            </p>
        </div>
    </div>
@endsection
