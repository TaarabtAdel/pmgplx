<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đăng nhập - KHGPLX</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #e9eef3;
            font-size: 0.925rem;
            color: #222;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .app-header {
            background: linear-gradient(180deg, #2b6aa3 0%, #1f4e79 100%);
            color: #fff;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }
        .login-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            border: 1px solid #c5cdd6;
        }
        .login-card .card-header {
            background: #f7f9fb;
            border-bottom: 1px solid #c5cdd6;
            color: #1f4e79;
            font-weight: 700;
        }
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
    </style>
</head>
<body>
    <div class="app-header">Quản Lý Trung Tâm— Trung Tâm Giáo Dục Nghề Nghiệp Mạnh Linh</div>

    <div class="login-wrap">
        <div class="card login-card">
            <div class="card-header">Đăng nhập hệ thống</div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 mb-3">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="so_dien_thoai">Số điện thoại</label>
                        <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control"
                               value="{{ old('so_dien_thoai') }}" maxlength="20" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="mat_khau">Mật khẩu</label>
                        <input type="password" id="mat_khau" name="mat_khau" class="form-control"
                               maxlength="100" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-navy btn-block">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
