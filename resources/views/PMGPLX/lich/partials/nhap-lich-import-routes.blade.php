@php
    $nhapLichRoutes = $nhapLichRoutes ?? [
        'store' => route('pmgplx.lich.nhap-file.store'),
        'preview' => route('pmgplx.lich.nhap-file.preview'),
        'preview_gv' => route('pmgplx.lich.nhap-file.preview-gv'),
        'preview_xe' => route('pmgplx.lich.nhap-file.preview-xe'),
        'preview_db' => route('pmgplx.lich.nhap-file.preview-db'),
        'to_gv' => route('pmgplx.lich.nhap-file.to-gv'),
        'to_xe' => route('pmgplx.lich.nhap-file.to-xe'),
        'to_db' => route('pmgplx.lich.nhap-file.to-db'),
        'confirm' => route('pmgplx.lich.nhap-file.confirm'),
        'cancel' => route('pmgplx.lich.nhap-file.cancel'),
        'create' => route('pmgplx.lich.nhap-file.create'),
    ];
    $isSlotReplaceImport = $isSlotReplaceImport ?? false;
@endphp

<nav class="mb-3" aria-label="Chế độ nhập lịch">
    <ul class="nav nav-pills flex-column flex-md-row border rounded p-1 bg-light">
        <li class="nav-item flex-fill text-center">
            <a class="nav-link {{ ! $isSlotReplaceImport ? 'active' : '' }}"
               href="{{ route('pmgplx.lich.nhap-file.create') }}">
                Nhập lịch từ file
                <small class="d-block font-weight-normal {{ ! $isSlotReplaceImport ? 'text-white-50' : 'text-muted' }}">
                    Xóa toàn bộ lịch khóa, ghi mới
                </small>
            </a>
        </li>
        <li class="nav-item flex-fill text-center">
            <a class="nav-link {{ $isSlotReplaceImport ? 'active' : '' }}"
               href="{{ route('pmgplx.lich.nhap-thay-the.create') }}">
                Nhập lịch thay thế
                <small class="d-block font-weight-normal {{ $isSlotReplaceImport ? 'text-white-50' : 'text-muted' }}">
                    Chỉ đổi GV / xe theo khung giờ
                </small>
            </a>
        </li>
    </ul>
</nav>
