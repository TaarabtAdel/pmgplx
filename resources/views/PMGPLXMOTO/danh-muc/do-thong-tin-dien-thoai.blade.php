<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dò thông tin học viên</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/do-thong-tin.css') }}?v={{ filemtime(public_path('css/do-thong-tin.css')) }}">
    <style>
        body { background: #f1f3f5; padding: .75rem .75rem 2rem; }
        .dtt-anh { height: 11rem; }
        .dtt-nut .btn { flex: 1 1 auto; padding: .5rem .6rem; font-size: .95rem; }
    </style>
</head>
<body>
    <h5 class="mb-1">Dò thông tin học viên</h5>
    @if (! $conHan)
        <div class="alert alert-warning">Phiên dò đã hết hạn. Trên máy tính, bấm «Dò lại từ đầu» rồi quét mã QR mới.</div>
    @else
        <p class="text-muted small mb-2">
            Chụp từng mặt giấy tờ — kết quả tự gửi về máy tính đang mở «Dò thông tin».
            <span id="ghi-chu-https"></span>
        </p>
        <div id="dtt-o" class="dtt-luoi"></div>
    @endif

    <script src="{{ asset('js/do-thong-tin.js') }}?v={{ filemtime(public_path('js/do-thong-tin.js')) }}"></script>
    @if ($conHan)
        <script>
            (function () {
                if (!window.DoThongTin.coTheQuet) {
                    document.getElementById('ghi-chu-https').textContent =
                        'Trang đang mở qua HTTP nên chỉ chụp từng ảnh (quét liên tục cần HTTPS).';
                }
                window.DoThongTin.ganO(document.getElementById('dtt-o'), {
                    urlOcr: @json(route('do-thong-tin.dien-thoai.ocr', $token, false)),
                    csrf: document.querySelector('meta[name="csrf-token"]').content,
                    onKetQua: function (mat, kq) {
                        if (navigator.vibrate) navigator.vibrate(60);
                    },
                });
            })();
        </script>
    @endif
</body>
</html>
