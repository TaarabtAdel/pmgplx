@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Sổ phân công GV & xe')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $validationErrors = $preview['validation_errors'] ?? [];
        $overlapErrors = $preview['overlap_errors'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Sổ phân công giáo viên, xe tập lái</span>
            <a href="{{ route('qldtoto.so-phan-cong-gv-xe-tap-lai.cancel') }}" class="btn btn-sm btn-outline-secondary">← Sửa lại</a>
        </div>
        <div class="card-body">
            <div><strong>Khoá PMGPLX:</strong> {{ $preview['ma_kh'] ?? '' }}</div>
            <div><strong>Tên khoá (DB phân công):</strong> {{ $preview['ten_khoa'] ?? '' }}</div>
            <div><strong>Trên file Word:</strong> KHOÁ {{ $preview['ten_khoa_doc'] ?? '' }} — Năm {{ $preview['nam'] ?? '' }}</div>
            <div class="mt-2">
                {{ number_format((int) ($meta['record_count'] ?? 0)) }} dòng,
                <strong class="text-success">{{ number_format($saveableCount) }} dòng có thể nhập Phân công đào tạo</strong>
                ({{ number_format((int) ($meta['skip_count'] ?? 0)) }} bỏ qua — «Xe tự động» hoặc thiếu GV).
            </div>
        </div>
    </div>

    @if ($validationErrors !== [])
        <div class="alert alert-danger">
            <strong>Lỗi dữ liệu</strong> (không nhập được Phân công đào tạo; vẫn xuất Word nếu có dòng):
            <ul class="mb-0 pl-3">
                @foreach ($validationErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($overlapErrors !== [])
        <div class="alert alert-warning">
            <strong>Cảnh báo — trùng lịch giáo viên / xe</strong> (vẫn xuất file; nhập DB nếu không có lỗi dữ liệu):
            <ul class="mb-0 pl-3">
                @foreach ($overlapErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-panel mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Giáo viên</th>
                            <th>Thời gian</th>
                            <th>Biển số</th>
                            <th>Nội dung</th>
                            <th>Nhập P.ĐT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['records'] as $row)
                            @php $skip = \App\Models\DaoTao\PhanCongDaoTao::skipReason($row); @endphp
                            <tr @class(['text-muted' => $skip !== null])>
                                <td>{{ $row['form_row'] ?? '' }}</td>
                                <td>{{ $row['HoTen'] ?? '' }}</td>
                                <td>{{ $row['ThoiGian'] ?? '' }}</td>
                                <td>{{ $row['BienSo'] ?? '' }}</td>
                                <td>{{ $row['NoiDungGiangDay'] ?? '' }}</td>
                                <td>
                                    @if ($skip)
                                        <span class="small">{{ $skip }}</span>
                                    @else
                                        <span class="badge badge-{{ ($row['import_action'] ?? '') === 'update' ? 'primary' : 'success' }}">
                                            {{ \App\Models\DaoTao\PhanCongDaoTao::importActionLabel($row['import_action'] ?? null) ?? 'Lưu' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
        <a href="{{ route('qldtoto.so-phan-cong-gv-xe-tap-lai.export') }}" class="btn btn-sm btn-success mr-2 mb-2">
            ⬇ Xuất file Word (.docx)
        </a>

        <form method="POST" action="{{ route('qldtoto.so-phan-cong-gv-xe-tap-lai.import') }}" class="mb-2 d-flex flex-wrap align-items-center"
              onsubmit="var c=document.getElementById('nhap_phan_cong'); if(!c||!c.checked){alert('Chọn «Nhập vào Phân công giáo viên» trước.');return false;} return confirm('Nhập {{ number_format($saveableCount) }} dòng vào Phân công đào tạo?');">
            @csrf
            <div class="custom-control custom-checkbox mr-3 mb-2">
                <input type="checkbox" class="custom-control-input" id="nhap_phan_cong" name="nhap_phan_cong" value="1"
                       @disabled(! $canImport)>
                <label class="custom-control-label @if(! $canImport) text-muted @endif" for="nhap_phan_cong">
                    Nhập vào Phân công giáo viên
                    (<a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}">Đào tạo → Danh sách</a>)
                </label>
            </div>
            <button type="submit" class="btn btn-sm btn-navy mb-2" @disabled(! $canImport)>Thực hiện nhập</button>
        </form>
    </div>
@endsection
