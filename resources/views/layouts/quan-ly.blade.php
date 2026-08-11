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
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-header">Phép Lái Xe — Trung Tâm Giáo Dục Nghề Nghiệp Mạnh Linh</div>

    <nav class="app-nav">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Trang chủ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dm.giao-vien.*') ? 'active' : '' }}"
                   href="{{ route('dm.giao-vien.index') }}">
                    Quản lý giáo viên
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dm.xe.*') ? 'active' : '' }}"
                   href="{{ route('dm.xe.index') }}">
                    Quản lý xe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('lich.gv.*', 'lich.ly-thuyet.*') ? 'active' : '' }}"
                   href="{{ route('lich.gv.index') }}">
                    Quản lý lịch làm việc giáo viên
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('lich.xe.*', 'lich.thuc-hanh.*') ? 'active' : '' }}"
                   href="{{ route('lich.xe.index') }}">
                    Quản lý lịch sử dụng xe tập lái
                </a>
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
        });
    </script>
    @stack('scripts')
</body>
</html>
