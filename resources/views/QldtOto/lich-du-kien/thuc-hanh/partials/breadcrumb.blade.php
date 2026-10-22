<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-white border py-2 small mb-3">
        <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
        <li class="breadcrumb-item">Làm lịch giảng dạy của khoá</li>
        <li class="breadcrumb-item">Làm lịch dự kiến</li>
        @isset($trangCon)
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}">Thực hành</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $trangCon }}</li>
        @else
            <li class="breadcrumb-item active" aria-current="page">Thực hành</li>
        @endisset
    </ol>
</nav>
