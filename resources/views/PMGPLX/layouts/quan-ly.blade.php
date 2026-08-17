<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý lịch') - KHGPLX</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <style>
        body {
            background: #e9eef3;
            font-size: 0.925rem;
            color: #222;
        }
        .app-header {
            background: linear-gradient(180deg, #2b6aa3 0%, #1f4e79 100%);
            color: #fff;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        .app-nav {
            background: #d7dee6;
            border-bottom: 1px solid #c5cdd6;
            padding: 0.4rem 0.75rem 0;
        }
        .app-nav .nav-link {
            color: #333;
            background: #eceff3;
            border: 1px solid #c5cdd6;
            border-bottom: none;
            border-radius: 0.25rem 0.25rem 0 0;
            margin-right: 2px;
            padding: 0.5rem 0.9rem;
        }
        .app-nav .nav-link.active {
            background: #fff;
            color: #1f4e79;
            font-weight: 700;
            position: relative;
            top: 1px;
        }
        .app-nav .nav-link:hover {
            color: #1f4e79;
            background: #f7f9fb;
        }
        .app-nav .nav-item.dropdown {
            position: relative;
        }
        .app-nav .dropdown-menu {
            margin-top: 0;
            border-radius: 0 0 0.25rem 0.25rem;
            border-color: #c5cdd6;
            min-width: 14rem;
            padding: 0.25rem 0;
        }
        .app-nav .dropdown-item {
            font-size: 0.925rem;
            padding: 0.45rem 0.9rem;
            color: #333;
        }
        .app-nav .dropdown-item:hover,
        .app-nav .dropdown-item:focus {
            background: #f0f4f8;
            color: #1f4e79;
        }
        .app-nav .dropdown-item.active {
            background: #1f4e79;
            color: #fff;
        }
        .app-nav .dropdown-submenu {
            position: relative;
        }
        .app-nav .dropdown-submenu > .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -0.25rem;
            margin-left: 0;
            display: none;
        }
        .app-nav .dropdown-submenu:hover > .dropdown-menu,
        .app-nav .dropdown-submenu.show > .dropdown-menu {
            display: block;
        }
        .app-nav .dropdown-submenu > .dropdown-item::after {
            display: inline-block;
            margin-left: 0.5rem;
            vertical-align: 0.15em;
            content: "";
            border-top: 0.3em solid transparent;
            border-right: 0;
            border-bottom: 0.3em solid transparent;
            border-left: 0.3em solid;
            float: right;
            margin-top: 0.45rem;
        }
        .page-wrap { padding: 1rem; }
        .card-panel {
            border: 1px solid #c5cdd6;
            margin-bottom: 1rem;
        }
        .card-panel .card-header {
            background: #f7f9fb;
            border-bottom: 1px solid #c5cdd6;
            color: #1f4e79;
            font-weight: 700;
            padding: 0.6rem 1rem;
        }
        .table-data thead th {
            background: #5b7ea3;
            color: #fff;
            border-color: #4c6d90;
            white-space: nowrap;
            font-weight: 600;
        }
        .table-data tbody tr { cursor: pointer; }
        .table-data tbody tr.selected,
        .table-data tbody tr.table-primary {
            background-color: #2f6fad !important;
            color: #fff;
        }
        .status-bar {
            margin-top: 0.75rem;
            padding: 0.45rem 0.75rem;
            background: #f0f3f6;
            border: 1px solid #c5cdd6;
            color: #444;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .day-calendar {
            border: 1px solid #ced4da;
            padding: 0.75rem;
            margin-bottom: 1rem;
            background: #fff;
        }
        .day-calendar .day {
            display: inline-block;
            width: 42px;
            height: 34px;
            line-height: 34px;
            text-align: center;
            margin: 4px;
            border: 1px solid #adb5bd;
            cursor: pointer;
            border-radius: 0.2rem;
            user-select: none;
        }
        .day-calendar .day:hover { background: #e9ecef; }
        .day-calendar .day.selected {
            background: #17a2b8;
            color: #fff;
            border-color: #138496;
        }
        .time-picker { display: flex; align-items: center; gap: 0.35rem; }
        .time-picker select { width: auto; min-width: 4.25rem; }
        .btn-navy {
            background: #1f4e79;
            border-color: #1f4e79;
            color: #fff;
        }
        .btn-navy:hover {
            background: #163a5c;
            border-color: #163a5c;
            color: #fff;
        }
        h5.section-title {
            color: #1f4e79;
            font-weight: 700;
            margin-top: 0.5rem;
        }

        /* Select2 single: căn giữa text + mũi tên */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px) !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
            padding-left: 0.75rem !important;
            padding-right: 2rem !important;
            width: 100%;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            position: absolute !important;
            top: 50% !important;
            right: 0.35rem !important;
            height: 1.25rem !important;
            width: 1.25rem !important;
            margin: 0 !important;
            transform: translateY(-50%);
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
            margin: 0 !important;
            position: static !important;
            border-width: 5px 4px 0 4px !important;
        }

        /* Native select: mũi tên không bị lệch */
        select.form-control {
            background-position: right 0.75rem center;
            padding-right: 2rem;
        }

        /* Multi chips (Select2 4.1 uses <button> for remove) */
        .select2-container--default .select2-selection--multiple {
            min-height: calc(1.5em + 0.75rem + 2px) !important;
            height: auto !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
            background: #fff !important;
            padding: 0.2rem 0.4rem !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple,
        .select2-container--default.select2-container--open .select2-selection--multiple {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 0.3rem !important;
            padding: 0 !important;
            margin: 0 !important;
            list-style: none !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            display: inline-flex !important;
            align-items: center !important;
            float: none !important;
            position: relative !important;
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100%;
            background: #e9ecef !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.2rem !important;
            color: #495057 !important;
            line-height: 1.4 !important;
            overflow: hidden;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding: 0.2rem 0.45rem !important;
            cursor: default;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            position: static !important;
            left: auto !important;
            top: auto !important;
            float: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: auto !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0.2rem 0.4rem !important;
            border: 0 !important;
            border-right: 1px solid #ced4da !important;
            border-radius: 0 !important;
            background: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
            color: #6c757d !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            cursor: pointer !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover,
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:focus {
            background: #dee2e6 !important;
            color: #212529 !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search--inline {
            float: none !important;
            flex: 1 1 8rem;
            display: inline-flex !important;
            min-width: 8rem;
        }
        .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
            margin: 0 !important;
            padding: 0.15rem 0 !important;
            height: 1.6rem !important;
            width: 100% !important;
            min-width: 8rem !important;
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        .select2-dropdown {
            z-index: 9999;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-header">Quản Lý Trung Tâm— Trung Tâm Giáo Dục Nghề Nghiệp Mạnh Linh</div>

    <nav class="app-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Trang chủ</a>
            </li>
            <li class="nav-item dropdown">
                @php
                    $pmgplxMenuActive = request()->routeIs('pmgplx.dm.*', 'pmgplx.lich.*', 'pmgplxold.*');
                    $lichActive = request()->routeIs('pmgplx.lich.*');
                @endphp
                <a class="nav-link dropdown-toggle {{ $pmgplxMenuActive ? 'active' : '' }}"
                   href="#"
                   id="navPmgplxDropdown"
                   role="button"
                   data-toggle="dropdown"
                   aria-haspopup="true"
                   aria-expanded="false">
                    Phần Mềm Giấy Phép Lái Xe
                </a>
                <div class="dropdown-menu" aria-labelledby="navPmgplxDropdown">
                    <a class="dropdown-item {{ request()->routeIs('pmgplx.dm.giao-vien.*') ? 'active' : '' }}"
                       href="{{ route('pmgplx.dm.giao-vien.index') }}">
                        Quản lý giáo viên
                    </a>
                    <a class="dropdown-item {{ request()->routeIs('pmgplx.dm.hoc-vien.*') ? 'active' : '' }}"
                       href="{{ route('pmgplx.dm.hoc-vien.index') }}">
                        Quản lý học viên
                    </a>
                    <a class="dropdown-item {{ request()->routeIs('pmgplxold.dm.hoc-vien.*') ? 'active' : '' }}"
                       href="{{ route('pmgplxold.dm.hoc-vien.index') }}">
                        Học viên (bản cũ)
                    </a>
                    <a class="dropdown-item {{ request()->routeIs('pmgplx.dm.xe.*') ? 'active' : '' }}"
                       href="{{ route('pmgplx.dm.xe.index') }}">
                        Quản lý xe
                    </a>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle {{ $lichActive ? 'active' : '' }}" href="#">
                            Lịch
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('pmgplx.lich.gv.*') ? 'active' : '' }}"
                               href="{{ route('pmgplx.lich.gv.index') }}">
                                Lịch làm việc giáo viên
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('pmgplx.lich.xe.*') ? 'active' : '' }}"
                               href="{{ route('pmgplx.lich.xe.index') }}">
                                Lịch sử dụng xe tập lái
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('pmgplx.lich.ly-thuyet.*') ? 'active' : '' }}"
                               href="{{ route('pmgplx.lich.ly-thuyet.create') }}">
                                Thêm lịch lý thuyết
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('pmgplx.lich.thuc-hanh.*') ? 'active' : '' }}"
                               href="{{ route('pmgplx.lich.thuc-hanh.create') }}">
                                Thêm lịch thực hành
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('pmgplx.lich.nhap-file.*') ? 'active' : '' }}"
                               href="{{ route('pmgplx.lich.nhap-file.create') }}">
                                Nhập lịch từ file
                            </a>
                        </div>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown">
                @php
                    $pdtActive = request()->routeIs('daotao.pdt.*');
                    $baoCaoActive = request()->routeIs('daotao.pdt.bc.*');
                    $congCuNhapActive = request()->routeIs('daotao.pdt.cong-cu-nhap.*');
                    $phanCongActive = request()->routeIs('daotao.pdt.phan-cong-dao-tao.*');
                @endphp
                <a class="nav-link dropdown-toggle {{ $pdtActive ? 'active' : '' }}"
                   href="#"
                   id="navPdtDropdown"
                   role="button"
                   data-toggle="dropdown"
                   aria-haspopup="true"
                   aria-expanded="false">
                    Phòng Đào Tạo
                </a>
                <div class="dropdown-menu" aria-labelledby="navPdtDropdown">
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle {{ $baoCaoActive ? 'active' : '' }}" href="#">
                            Báo cáo
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('daotao.pdt.bc.luu-luong-dao-tao') ? 'active' : '' }}"
                               href="{{ route('daotao.pdt.bc.luu-luong-dao-tao') }}">
                                Báo cáo Lưu lượng đào tạo
                            </a>
                        </div>
                    </div>
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle {{ $congCuNhapActive ? 'active' : '' }}" href="#">
                            Công cụ nhập
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao*') ? 'active' : '' }}"
                               href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao') }}">
                                Nhập file tiến độ đào tạo
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien*') ? 'active' : '' }}"
                               href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien') }}">
                                Nhập file sổ phân công giáo viên
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten*') ? 'active' : '' }}"
                               href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten') }}">
                                Nhập file in bảng tên
                            </a>
                        </div>
                    </div>
                    <div class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle {{ $phanCongActive ? 'active' : '' }}" href="#">
                            Phân Công Đào Tạo
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item {{ request()->routeIs('daotao.pdt.phan-cong-dao-tao.danh-sach') ? 'active' : '' }}"
                               href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}">
                                Danh sách phân công
                            </a>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </nav>

    <div class="page-wrap">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <div class="status-bar">
            <span>Tài khoản đăng nhập: ADMIN</span>
            <span>Hôm nay ngày: {{ now()->format('d/m/Y') }}</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('table.table-data tbody').on('click', 'tr', function () {
                $(this).addClass('selected').siblings().removeClass('selected');
            });

            $('.app-nav .dropdown-submenu > a').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).parent().toggleClass('show').siblings('.dropdown-submenu').removeClass('show');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
