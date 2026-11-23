{{-- Quy ước ghép file → ô LT/MP/H/CC/ĐT (vắng, thi lại, tổng hợp) — hiển thị trên màn chi tiết / tạo / xem trước. --}}
@php
    $laThiLaiBang = filter_var($laThiLai ?? false, FILTER_VALIDATE_BOOLEAN);
@endphp
<div class="card card-panel mb-3 border-info">
    <div class="card-header py-2 bg-light">
        <button class="btn btn-link btn-sm text-dark p-0 font-weight-bold text-decoration-none" type="button"
                data-toggle="collapse" data-target="#ktm-bang-quy-uoc" aria-expanded="false">
            Quy ước ghép file &amp; vắng <span class="text-muted font-weight-normal">(bấm để mở/đóng)</span>
        </button>
    </div>
    <div id="ktm-bang-quy-uoc" class="collapse">
        <div class="card-body py-2 small">
            <p class="text-muted mb-2 mb-md-1">
                <strong>V…</strong> = vắng <em>rõ môn</em> (vd. không có tên trong file điểm / DS đạt–trượt khi file đó đã tải).
                <strong>-</strong> = chưa có điểm hoặc chưa áp dụng môn — <em>không</em> đồng nghĩa «vắng» trên từng ô,
                trừ khi <strong>tổng hợp</strong> cũng là <strong>-</strong> (thiếu điểm môn bắt buộc).
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-2 bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th>Môn</th>
                            <th>Mã vắng</th>
                            <th>Khi đã tải file môn</th>
                            <th>Chưa tải file môn (kỳ mới)</th>
                            @if ($laThiLaiBang)
                                <th>Thi lại — không tải lại file môn</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Lý thuyết</td>
                            <td><strong>VLT</strong></td>
                            <td>Có Đ/KĐ trong file LT → ghép; không thấy trong file → <strong>VLT</strong></td>
                            <td>Ô <strong>-</strong> (không gán vắng)</td>
                            @if ($laThiLaiBang)
                                <td>Giữ kết quả kỳ gốc</td>
                            @endif
                        </tr>
                        <tr>
                            <td>Mô phỏng</td>
                            <td><strong>VMP</strong></td>
                            <td colspan="{{ $laThiLaiBang ? 3 : 2 }}">Giống LT (file MP / khớp SBD–CCCD)</td>
                        </tr>
                        <tr>
                            <td>Chữ chi (CC)</td>
                            <td><strong>VCC</strong></td>
                            <td colspan="{{ $laThiLaiBang ? 3 : 2 }}">File CC: cột «Điểm CC»; khớp CCCD hoặc Họ tên + Ngày sinh</td>
                        </tr>
                        <tr>
                            <td>Hình</td>
                            <td><strong>VH</strong></td>
                            <td colspan="{{ $laThiLaiBang ? 3 : 2 }}">
                                Trong DS đạt → Đ; DS trượt → KĐ. Đủ <em>cả hai</em> file đạt &amp; trượt, không có tên, LT = Đ → <strong>VH</strong>.
                                Thiếu một file → không tự VH (giữ cũ hoặc <strong>-</strong>).
                            </td>
                        </tr>
                        <tr>
                            <td>Đường trường</td>
                            <td><strong>VĐ</strong></td>
                            <td colspan="{{ $laThiLaiBang ? 3 : 2 }}">Giống Hình; vắng khi đủ 2 file và H = Đ → <strong>VĐ</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-2 bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 28%;">Chủ đề</th>
                            <th>Quy ước</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Phải thi môn nào</td>
                            <td>
                                Cột <strong>Ghi chú</strong> trên DS sát hạch:
                                trống → thi đủ LT, MP, H, Đường, CC;
                                có ghi chú → chỉ các mã <strong>LT, MP, H, Đ, CC</strong> (môn không liệt kê → <strong>-</strong>).
                            </td>
                        </tr>
                        <tr>
                            <td>Không có tên trong danh sách</td>
                            <td>
                                <strong>Danh sách thí sinh gốc:</strong> không có dòng → không xuất hiện trên báo cáo chi tiết.<br>
                                <strong>File LT / MP / CC (đã tải):</strong> có trên DS gốc nhưng không khớp trong file → <strong>VLT / VMP / VCC</strong> (vắng môn), không ghi <strong>-</strong>.<br>
                                <strong>File H / Đường đạt &amp; trượt (đủ cả hai):</strong> không có tên ở cả hai → <strong>VH</strong> / <strong>VĐ</strong> nếu đủ điều kiện (LT/H đã Đ); thiếu một file → <strong>-</strong> hoặc giữ cũ, không tự VH/VĐ.<br>
                                <strong>Chưa tải file môn:</strong> không coi «không có tên» là vắng → ô <strong>-</strong>.
                            </td>
                        </tr>
                        <tr>
                            <td>Dấu <strong>-</strong> trên từng môn</td>
                            <td>
                                Chưa upload file môn (kỳ mới), thi lại chưa cập nhật môn, môn không nằm trong ghi chú «phải thi», hoặc H/Đường thiếu file đạt/trượt.
                                <strong>Một ô «-» đơn lẻ không đưa vào DS vắng</strong> nếu tổng hợp vẫn Đ/KĐ (vd. chỉ thi LT, MP chưa nộp file).
                            </td>
                        </tr>
                        <tr>
                            <td>Tổng hợp Đ / KĐ / -</td>
                            <td>
                                Với môn phải thi: còn ô <strong>-</strong> → tổng <strong>-</strong>;
                                đủ dữ liệu: mọi môn <strong>Đ</strong> → tổng <strong>Đ</strong>;
                                có <strong>KĐ</strong> hoặc <strong>V…</strong> → tổng <strong>KĐ</strong>.
                            </td>
                        </tr>
                        <tr>
                            <td>Coi là «vắng» (DS xuất / BBTH)</td>
                            <td>
                                <strong>(1)</strong> Bất kỳ môn <strong>V…</strong> (đã tải file mà không có tên / không khớp điểm).<br>
                                <strong>(2)</strong> Hoặc cột <strong>Tổng</strong> = <strong>-</strong> (thiếu điểm ít nhất một môn bắt buộc).<br>
                                Chỉ có ô môn <strong>-</strong> mà tổng vẫn <strong>Đ</strong>/<strong>KĐ</strong> → <em>không</em> vào DS vắng.
                            </td>
                        </tr>
                        @if ($laThiLaiBang)
                            <tr>
                                <td>Thi lại — file điểm</td>
                                <td>
                                    <strong>LT:</strong> chỉ dòng có cột «Kết quả»;
                                    <strong>MP:</strong> chỉ «Đạt»;
                                    <strong>CC:</strong> chỉ dòng có số «Điểm CC».
                                    Thiếu trong file = giữ kỳ gốc (không V…).
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>Kỳ thi lần 1</td>
                                <td>
                                    File LT: cột <strong>Số CMT/CCCD</strong> theo tiêu đề phải có dữ liệu (trống → báo lỗi, không ghép).
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <p class="mb-0 text-muted">
                Ô bảng: <strong>Đ</strong>, <strong>KĐ</strong>, <strong>V…</strong> (vắng môn), <strong>-</strong> (chưa ghép / không áp dụng).
            </p>
        </div>
    </div>
</div>