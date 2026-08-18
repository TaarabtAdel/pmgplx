<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In bảng tên học viên</title>
    @include('DaoTao.cong-cu-nhap.partials.bang-ten-cards-styles')
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        @media print {
            html, body {
                width: auto;
                height: auto;
            }
        }
    </style>
</head>
<body>
    @include('DaoTao.cong-cu-nhap.partials.bang-ten-cards', ['preview' => $preview])

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
        window.addEventListener('afterprint', function () {
            window.close();
        });
    </script>
</body>
</html>
