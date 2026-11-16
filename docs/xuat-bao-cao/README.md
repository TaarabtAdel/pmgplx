# PROMPT CHO CURSOR — Chức năng "Xuất báo cáo kỳ kiểm tra kết thúc môn học (KTM)"

> Cách dùng: copy toàn bộ file này vào Cursor, đồng thời đưa 4 file đính kèm (mục 2) vào thư mục `docs/xuat-bao-cao/` của dự án để Cursor đọc.

---

## 1. Mục tiêu

Hệ thống quản lý đào tạo lái xe **đã có sẵn**. Hãy bổ sung chức năng **Xuất báo cáo kỳ kiểm tra KTM** cho Trung tâm GDNN Mạnh Linh:

- **Đầu vào:** 1 file Excel dữ liệu kỳ kiểm tra (cấu trúc mục 3).
- **Đầu ra:** 3 file Excel, giữ **đúng bố cục, định dạng, ô gộp** của 3 file mẫu:
  1. **BBTH** – Biên bản tổng hợp kết quả kỳ kiểm tra kết thúc môn học
  2. **BC CHI TIẾT** – Báo cáo chi tiết (từng thí sinh)
  3. **BC CHUNG** – Báo cáo chung (số liệu tổng hợp theo hạng)

Hiện tại người dùng làm thủ công bằng 1 workbook nhiều sheet liên kết bằng công thức (có cả liên kết ngoài bị lỗi). Chức năng mới thay thế quy trình đó: **tính toán bằng code, không phụ thuộc công thức Excel**.

**Trước khi viết code:** đọc repo để xác định stack, cấu trúc thư mục, cách đặt menu/route, thư viện Excel đang dùng (nếu có) và bám theo đúng quy ước đó. Không tự đổi stack. Nếu chưa có thư viện đọc/ghi Excel, đề xuất 1 thư viện phù hợp với stack rồi mới cài.

Gợi ý vị trí menu: nhóm "Sau khi hoàn thành khoá" (nếu menu hiện tại có). Nếu chưa chắc, hỏi lại trước khi tạo menu.

## 2. Tệp đính kèm

| File | Vai trò |
|---|---|
| `file_nhap/FILE_NHAP_mau.xlsx` | File nhập mẫu: 6 sheet `DS`, `DS SH`, `DS VẮNG THI`, `DS THI ĐẠT`, `DS KO ĐẠT`, `Sheet1` (đã chuyển công thức thành giá trị) |
| `file_xuat_mau/MAU_BBTH_bien_ban_tong_hop.xlsx` | Mẫu xuất 1 (sheet `BBTH`) |
| `file_xuat_mau/MAU_BC_CHI_TIET_bao_cao_chi_tiet.xlsx` | Mẫu xuất 2 (sheet `BC CHI TIẾT`) |
| `file_xuat_mau/MAU_BC_CHUNG_bao_cao_chung.xlsx` | Mẫu xuất 3 (sheet `BC CHUNG`) |

Dữ liệu mẫu: khóa **KIỂM TRA LẠI KTM B-K55 TTML**, ngày thi **18/10/2026**, 34 hồ sơ, 33 người dự thi.

**Quy tắc xuất file:** dùng chính file mẫu làm template (copy → điền ô → lưu), **không dựng lại từ đầu**, để giữ nguyên font, viền, độ rộng cột, ô gộp, căn lề, vùng in. Mọi ô điền phải là **giá trị**, không để công thức tham chiếu sheet/file khác.

## 3. File nhập — cấu trúc và cách đọc

Quy trình gốc của người dùng: `DS` (dữ liệu thô) → `DS SH` (danh sách dự thi) → các danh sách phụ → `Sheet1` (bảng số liệu + kết quả từng người) → 3 mẫu xuất.

### 3.1. Sheet `DS` — dữ liệu thô từ phần mềm sát hạch
- Hàng 1 là tiêu đề. Khối **phải** là dữ liệu gốc: `J` STT, `N` SBD, `O` Họ và tên, `U` Số CMT/định danh, `Y` Ngày sinh (chuỗi `dd/mm/yyyy`), `AA` Hạng GPLX, `AB` **Ghi chú**.
- Khối **trái** (`A..G`) chỉ là bản sao bằng công thức (không có Ghi chú). Ưu tiên đọc khối phải.
- Dữ liệu từ hàng 2; các hàng toàn số 0 phía dưới (hàng ~96–101) là hàng rỗng, **bỏ qua**.
- **Ghi chú** quyết định thí sinh phải thi nội dung nào. Các giá trị trong dữ liệu mẫu: `LT H Đ`, `H Đ`, `CC`, `Đ`, `Đ CC`, `MP`, `LT MP H Đ CC`. Mã: `LT` lý thuyết, `MP` mô phỏng, `H` lái trong hình, `Đ` lái trên đường, `CC` tiến lùi chữ chi. Cho phép tách theo khoảng trắng, bỏ khoảng trắng thừa, không phân biệt hoa/thường.

### 3.2. Sheet `DS SH` — danh sách thí sinh dự thi (nguồn thông tin chung của kỳ thi)
- `C7` tên khóa thi (vd `KIỂM TRA LẠI KTM B-K55 TTML`), `G7` tổng số, `C8` ngày thi (chuỗi `dd/mm/yyyy`).
- Bảng thí sinh bắt đầu hàng 11: `A` STT, `B` SBD, `C` Họ tên, `D` Số định danh, `E` Ngày sinh, `G` Hạng, `H` Ghi chú. Dừng khi gặp hàng trống/0; hàng chân trang "Ủy Viên / Thư Ký / Chủ tịch hội đồng" không phải dữ liệu.
- Thứ tự STT của `DS SH` là **thứ tự in trong BC CHI TIẾT**.

### 3.3. Các sheet `DS VẮNG THI`, `DS THI ĐẠT`, `DS KO ĐẠT`
Cùng bố cục với `DS SH`, là danh sách lọc theo trạng thái. **Không bắt buộc** để tính 3 báo cáo (trạng thái sẽ được tính từ `Sheet1`). Dùng để đối chiếu: nếu lệch với kết quả tính được thì cảnh báo, không chặn xuất.

### 3.4. Sheet `Sheet1` — số liệu và kết quả từng thí sinh (**nguồn chính để tính**)
Hàng 1–5 là tiêu đề + hàng tổng của hạng B. Thí sinh hạng B nằm ở **hàng 6–203**, mỗi hàng 1 người, khóa tra cứu là **Số định danh (cột D)**:

| Cột | Nội dung |
|---|---|
| A | SBD |
| B | Họ và tên |
| C | Ngày sinh |
| D | Số định danh (CCCD) |
| F | Tổng số hồ sơ (1) |
| G | Tổng số dự thi (1 nếu có mặt, 0 nếu vắng) |
| H / I / J | Lý thuyết: tổng số / đạt / rớt |
| K / L / M | Mô phỏng: tổng số / đạt / rớt |
| N / O / P | Lái trong hình: tổng số / đạt / rớt |
| Q / R / S | Lái trên đường: tổng số / đạt / rớt |
| T | Kết quả đạt chung (1/0) |
| U | Ghi chú (nội dung phải thi) |
| W / X / Y / Z | Trạng thái từng nội dung LT / MP / H / Đ: `Đ`, `KĐ`, `VLT`/`VMP`/`VH`/`VĐ` (vắng), hoặc `0` |
| AA | KQ chung `Đ`/`KĐ` |
| AB..AE | Cờ "đã thi" (1) của LT/MP/H/Đ → chính là "Số lần SH" |

Các khối khác trong `Sheet1` (hiện đều rỗng trong dữ liệu mẫu, nhưng phải có chỗ trong thiết kế):
- Hàng 204: `B01` (hàng 205–587) · Hàng 630: `C1 THI MỚI` · Hàng 928: `C1 THI LẠI` · Hàng 935: `QUÁ HẠN` (hàng 936–967) → khớp các dòng "B TTML / C1 THI MỚI - TCN / C1 THI LẠI - TCN / QUÁ HẠN GPLX HẠNG B & C1" của BC CHUNG.
- Hàng 969–1075: phần **b. Thí sinh sát hạch lại để cấp đổi GPLX** (B, C1, C, D1, D2…), hàng 1076 tổng cộng.

> Thiết kế: gom thí sinh theo **nhóm (đối tượng)** + **hạng**, có bảng ánh xạ cấu hình nhóm → dòng trong 3 mẫu. Phiên bản này chỉ cần số liệu thật cho nhóm "B TTML"; các nhóm còn lại xuất số 0 đúng như mẫu, nhưng đừng hard-code để sau mở rộng được.

## 4. Quy tắc nghiệp vụ (suy ra từ công thức của file gốc)

Với mỗi thí sinh và mỗi nội dung `c ∈ {LT, MP, H, Đ, CC}`:
- **Phải thi** nếu mã `c` có trong Ghi chú.
- **Vắng** nếu phải thi mà thí sinh vắng (mã `VLT/VMP/VH/VĐ/VCC`, hoặc Sheet1 G = 0).
- **Đã thi** nếu có kết quả `Đ` hoặc `KĐ` → "Số lần SH" = 1; ngược lại 0.
- **Đạt** nếu kết quả `Đ`; **Rớt** nếu `KĐ`.
- **Kết quả chung** = `Đ` khi thí sinh có mặt và **tất cả** nội dung phải thi đều `Đ`; ngược lại `KĐ`. Người vắng → `KĐ` và không tính vào "Tổng số dự thi".

Tổng hợp theo nhóm/hạng (mỗi nội dung có 3 số: **Tổng số** = đạt + rớt, **Đạt**, **Rớt**):
- Tổng số hồ sơ = số thí sinh trong nhóm; Tổng số dự thi = số thí sinh có mặt.
- Số vắng = hồ sơ − dự thi.
- Kết quả đạt = số thí sinh có kết quả chung `Đ`.
- Dòng **Cộng** = tổng các dòng nhóm phía trên.
- **Tỷ lệ đạt** (dùng trong BBTH) = Kết quả đạt ÷ Tổng số dự thi, làm tròn 1 chữ số thập phân, hiển thị dạng `90,9%`. *(Xác nhận với người dùng; có thể cho cấu hình mẫu số là "dự thi" hay "hồ sơ".)*

Lái trên đường (`Đ`) chỉ tính khi thí sinh **đã đạt Lái trong hình (H)** hoặc không phải thi H. Thí sinh rớt H thì Đ để trống, không tính là rớt Đ (xem mục 7).

**Cột CC:** file nhập hiện **không có** cột kết quả CC. Quy ước mặc định: có `CC` trong Ghi chú và có mặt → coi là `Đ` (Số lần SH = 1); vắng → `VCC`. Cho phép người dùng sửa/nhập kết quả CC trên màn hình trước khi xuất.

## 5. Ba file xuất — vị trí ô và nguồn dữ liệu

Số hàng/cột dưới đây theo Excel (1-based) trong **file mẫu đã tách**.

### 5.1. BBTH (`MAU_BBTH_...xlsx`, sheet `BBTH`)

| Ô | Nội dung | Nguồn |
|---|---|---|
| A1, A2 / G1, G2 | Quốc hiệu, tiêu ngữ, tên trung tâm, hội đồng | Giữ nguyên (cấu hình) |
| I4 | `Quảng Trị, ngày DD tháng MM năm YYYY` | Cấu hình "ngày lập báo cáo" (mặc định = ngày thi) |
| A5, A6 | `BIÊN BẢN` / `TỔNG HỢP KẾT QUẢ KỲ KIỂM TRA KẾT THÚC MÔN HỌC` | Giữ nguyên |
| **A8** | Đoạn văn: ngày họp, giờ họp, địa điểm, **số thí sinh**, **tên khóa**, **số + ngày Quyết định** thành lập hội đồng | Tham số cấu hình (mục 6). *Mẫu đang chứa dữ liệu cũ của khóa khác: "16/11/2025, 15h30, 45 thí sinh, B01K28, QĐ ngày 07/11/2025" → phải thay hết* |
| B9..B12 | Danh sách 4 thành viên hội đồng (Chủ tịch, Phó chủ tịch, Ủy viên, Thư ký) | Cấu hình |
| **H16** | Tổng số hồ sơ đăng ký dự thi | Σ hồ sơ tất cả nhóm |
| **O16** | Số vắng không đến thi | hồ sơ − dự thi |
| **H17** | Tổng số có mặt dự thi | Σ dự thi |
| B20 | `Thí sinh hạng B` | Giữ/cấu hình |
| **Hàng 24, 25, 26** | Mục a: hàng 24 = nhóm "B TTML", hàng 25 = nhóm thứ hai (`B01`), hàng 26 = **Cộng** | Mục 4 |
| **Hàng 30–34, 36** | Mục b (cấp lại GPLX): B, C1, C, D1, D2; hàng 36 = **Cộng** | Mục 4 (hiện = 0) |
| B39 | `- Như vậy tổng số thí sinh đạt … là: [N] đạt tỷ lệ: [x%]` | N = V26 + V36; tỷ lệ theo mục 4 |
| X38, X39 | Ô nháp (`30`, `Nhớ sửa`) | **Xóa**, không xuất ra file |
| B40..B44 | Các dòng nhận xét (vi phạm nội quy: Không; đúng quy chế; cơ sở vật chất tốt; an toàn; khuyết điểm: không) | Cấu hình, cho sửa |
| B46 / M46 | `ỦY VIÊN` / `CHỦ TỊCH` | Giữ |
| B51, F51, M51 | Tên người ký: Nguyễn Cửu Phúc, Mai Thị Lệ Huyền, Nguyễn Thị Vinh | Cấu hình |
| G53, G58 | `THƯ KÝ` / Hoàng Hải | Cấu hình |

**Cột trong bảng hàng 24–26 và 30–36** (tiêu đề ở hàng 22–23):
`E` Tổng hồ sơ · `F` Tổng dự thi · `G H I` LT (tổng/đạt/rớt) · `J K L` MP · `M N O` Hình · `P Q R` Tiến lùi chữ chi (CC) · `S T U` Đường · `V` Kết quả đạt.

### 5.2. BC CHI TIẾT (`MAU_BC_CHI_TIET_...xlsx`, sheet `BC CHI TIẾT`)

| Ô | Nội dung |
|---|---|
| B1, B2 / M1, M2 | Tên trung tâm, hội đồng / quốc hiệu, tiêu ngữ (giữ nguyên) |
| O4 | `Quảng Trị, ngày DD tháng MM năm YYYY` |
| A5, A6 | `BÁO CÁO CHI TIẾT` / `KẾT QUẢ KỲ KIỂM TRA` |
| A8 | `1. Trung tâm sát hạch: Trung tâm Sát hạch lái xe thuộc Trung tâm GDNN Mạnh Linh` |
| A9 | `2. Ngày Kiểm Tra: dd/mm/yyyy` (= ngày thi) |
| Hàng 11–12 | Tiêu đề bảng (giữ nguyên) |
| **Hàng 13 → 13+N−1** | Mỗi thí sinh 1 hàng, theo thứ tự STT của `DS SH` |
| Cuối bảng (mẫu: O47, O48, O52) | `KT. GIÁM ĐỐC` / `P. GIÁM ĐỐC` / tên người ký — **dịch xuống theo số hàng thực tế** |

Cột mỗi hàng thí sinh (ô gộp sẵn trong mẫu cho `D:F`, `G:I`, `J:L`):
`B` STT · `C` Họ và tên · `D` Ngày sinh · `G` Số định danh · `J` Hạng ·
`M` LT · `N` Số lần SH · `O` MP · `P` Số lần SH · `Q` TH lái trong hình · `R` Số lần SH · `S` CC · `T` Số lần SH · `U` TH lái trên đường · `V` Số lần SH · `W` Tổng hợp (`Đ`/`KĐ`).

Giá trị ô kết quả: `Đ`, `KĐ`, `VLT`/`VMP`/`VH`/`VCC`/`VĐ` nếu vắng; nội dung không phải thi **để trống hoặc 0 giống mẫu** (dữ liệu mẫu để `0`; đề xuất để trống cho sạch, nhưng làm cấu hình để giống mẫu cũ nếu người dùng muốn).
**Các cột phụ `X..AH`** của mẫu (Ghi chú, CCCD, VLOOKUP…) là cột làm việc, **không xuất**; xóa hẳn khỏi file xuất.
Nếu số thí sinh khác 34: thêm/bớt hàng, **sao chép định dạng + ô gộp** của hàng dữ liệu mẫu, tự dịch phần chữ ký xuống.

### 5.3. BC CHUNG (`MAU_BC_CHUNG_...xlsx`, sheet `BC CHUNG`)

| Ô | Nội dung |
|---|---|
| B1, B2 / M1, M2 | Tên trung tâm, hội đồng / quốc hiệu, tiêu ngữ |
| O4 | `Quảng Trị, ngày DD tháng MM năm YYYY` |
| A5, A6 | `BÁO CÁO CHUNG` / `KẾT QUẢ KỲ KIỂM TRA` |
| A8, A9 | `1. Trung tâm sát hạch: …` / `2. Ngày Kiểm Tra: dd/mm/yyyy` |
| Hàng 13–17 | Nhóm: `B TTML`, `B TTML` (nhóm 2), `C1 THI MỚI - TCN`, `C1 THI LẠI - TCN`, `QUÁ HẠN GPLX HẠNG B & C1` |
| Hàng 18 | `Cộng` |
| O20, P21, P27 | `KT. GIÁM ĐỐC` / `P. GIÁM ĐỐC` / `TRƯƠNG HOÀNG LINH` |

Cột số liệu mỗi nhóm: `F` Hồ sơ · `G` Dự thi · `H I J` LT · `K L M` MP · `N O P` Hình · `Q R S` CC · `T U V` Đường · `W` Kết quả đạt. **Số liệu lấy y hệt dòng nhóm tương ứng của BBTH** (BC CHUNG không tự tính riêng, để 2 báo cáo không bao giờ lệch nhau).

## 6. Tham số cấu hình (màn hình trước khi xuất, lưu lại để lần sau dùng lại)

- Tên khóa, ngày thi (mặc định đọc từ `DS SH`, cho sửa)
- Ngày lập báo cáo (mặc định = ngày thi)
- Ngày họp + giờ họp + địa điểm họp của hội đồng
- Số Quyết định + ngày Quyết định thành lập hội đồng (để trống thì để chỗ trống đúng như mẫu `Số:         /QĐ-TTML`)
- Danh sách 4 thành viên hội đồng (họ tên, chức vụ, vai trò) và người ký từng báo cáo
- Nội dung nhận xét ở BBTH (mục II, B40..B44)
- Cách tính tỷ lệ đạt (mẫu số = dự thi / hồ sơ)

Không hard-code tên người, số QĐ, ngày tháng trong code.

## 7. Mâu thuẫn phát hiện trong file mẫu (phải xử lý và cảnh báo trên giao diện)

1. **Nội dung BBTH A8 là của khóa khác** (16/11/2025, 45 thí sinh, B01K28) → phải sinh lại từ tham số, không giữ chuỗi cũ.
2. **Ngày BBTH (I4) ghi 12/07/2026**, trong khi kỳ thi là 18/10/2026 → dùng ngày cấu hình.
3. **Chữ ký BBTH có "Mai Thị Lệ Huyền" (Ủy viên) nhưng danh sách thành viên mục 1 không có tên này** (chỉ có Vinh, Linh, Phúc, Hải) → để người dùng xác nhận, không tự sửa.
4. **Tổng LÁI TRÊN ĐƯỜNG của mẫu là 24/23/1, nhưng chi tiết từng người chỉ có 23 người thi Đ, 23 đạt, 0 rớt.** Chênh 1 vì thí sinh HỒ THỊ CAM (rớt H) bị đếm là "rớt Đ" ở `Sheet1` (Q9=1, R9=0), trong khi 2 thí sinh THẢO cũng rớt H thì không bị đếm; BC CHI TIẾT của Cam ghi Đ = 0. → Quy tắc đã chọn ở mục 4 (rớt H thì không tính Đ). **Cần người dùng xác nhận.**
5. **Tổng CC của mẫu là 10/10/0, nhưng chỉ có 9 người có mặt** (TRƯƠNG QUỐC VŨ vắng, BC CHI TIẾT ghi `VCC`). Ô P24/Q24 trong BBTH là số gõ tay. → Tính từ chi tiết: CC = 9/9/0. **Cần người dùng xác nhận.**
6. BBTH có công thức lệch cột/sai (vd: `V25 = Sheet1!T204`, `O30 = Sheet1!P971`, `R24 = SUM(R22:R23)`, `J26 = J24`, `V26 = SUM(V24:W25)`, và liên kết ngoài `[1]Sheet1!…` bị hỏng) → **không sao chép công thức**, tính lại từ dữ liệu.
7. Ô `X38`, `X39` ("Nhớ sửa") là ghi chú nháp → không xuất.

Với mỗi điểm 4–5, nếu số tính được khác số mẫu, hiển thị cảnh báo ở bước xem trước, không chặn xuất.

## 8. Dữ liệu kiểm thử và kết quả kỳ vọng (từ `FILE_NHAP_mau.xlsx`)

Viết test tự động cho phần tính toán với dữ liệu mẫu:

| Chỉ tiêu | Tổng số | Đạt | Rớt |
|---|---|---|---|
| Hồ sơ / Dự thi | 34 / 33 | | |
| Vắng | 1 (TRƯƠNG QUỐC VŨ, SBD 61, ghi chú `LT MP H Đ CC`) | | |
| Lý thuyết (LT) | 10 | 10 | 0 |
| Mô phỏng (MP) | 1 | 1 | 0 |
| Lái trong hình (H) | 19 | 16 | 3 |
| Tiến lùi chữ chi (CC) | 9 *(mẫu cũ: 10)* | 9 | 0 |
| Lái trên đường (Đ) | 23 *(mẫu cũ: 24)* | 23 | 0 *(mẫu cũ: 1)* |
| **Kết quả đạt** | | **30** | |

- 3 thí sinh rớt: HỒ THỊ CAM, NGUYỄN THỊ PHƯƠNG THẢO, TRẦN HOÀNG NGỌC THẢO (đều rớt H).
- Tỷ lệ đạt = 30 / 33 = **90,9%**.
- Phân bố Ghi chú: `LT H Đ` 10 · `H Đ` 9 · `CC` 6 · `Đ` 4 · `Đ CC` 3 · `MP` 1 · `LT MP H Đ CC` 1.
- BC CHI TIẾT phải có đúng 34 hàng, STT 1–34, hàng cuối là TRƯƠNG QUỐC VŨ với `VLT`, `VMP`, `VH`, `VCC`, `VĐ`, tổng hợp `KĐ`.
- Mẫu ví dụ để so từng ô: STT 4 HỒ THỊ CAM → LT `Đ` (1 lần), H `KĐ` (1 lần), Đ trống, tổng hợp `KĐ`.

Nếu người dùng xác nhận giữ cách đếm của mẫu cũ ở mục 7.4/7.5, đổi kỳ vọng tương ứng (Đ = 24/23/1, CC = 10/10/0) và ghi rõ đó là ngoại lệ.

## 9. Yêu cầu kỹ thuật và giao diện

1. **Tách lớp rõ ràng:** (a) đọc file nhập → mô hình dữ liệu chuẩn; (b) tính toán/tổng hợp (hàm thuần, có unit test); (c) ghi 3 file xuất từ template. UI chỉ gọi 3 lớp này.
2. **Màn hình "Xuất báo cáo KTM":**
   - Chọn file nhập (`.xls`/`.xlsx`). Nếu hệ thống chưa đọc được `.xls` cũ, cho phép `.xlsx` trước và nói rõ giới hạn.
   - Hiển thị thông tin nhận diện: tên khóa, ngày thi, số hồ sơ, số dự thi, số vắng.
   - Form tham số (mục 6).
   - **Xem trước số liệu tổng hợp** + danh sách cảnh báo (mục 7, thiếu/trùng định danh, Ghi chú lạ, kết quả mâu thuẫn).
   - 3 nút tải: BBTH / BC CHI TIẾT / BC CHUNG, và 1 nút "Tải cả 3 (.zip)".
3. **Kiểm tra dữ liệu đầu vào:** Số định danh trùng; thí sinh có trong Sheet1 mà không có trong `DS SH` (và ngược lại); Ghi chú chứa mã lạ; Ngày sinh sai định dạng; thiếu kết quả của nội dung bắt buộc. Báo lỗi theo dạng "Sheet – hàng – nội dung", tiếng Việt.
4. **Tên file xuất** gợi ý: `BBTH_<tên khóa>_<yyyyMMdd>.xlsx`, `BC_CHI_TIET_…`, `BC_CHUNG_…`.
5. **Tiếng Việt có dấu:** xử lý đúng UTF-8, kiểu dữ liệu: Số định danh và ngày sinh là **chuỗi** (giữ số 0 đầu); không để Excel đổi sang số/ngày.
6. Giữ vùng in, khổ giấy, hướng giấy của mẫu.
7. Không thêm dependency nặng nếu stack hiện có đã làm được.

## 10. Không làm trong phạm vi này

- Không làm phần xếp lịch, đăng ký thí sinh hay nhập điểm từng nội dung (chỉ đọc kết quả có sẵn trong file nhập).
- Không thay đổi các màn hình/menu khác của hệ thống.
- Không tự đoán các giá trị mục 7 khi người dùng chưa xác nhận: dùng mặc định đã ghi và cảnh báo.

## 11. Khi hoàn thành

Báo lại: danh sách file đã tạo/sửa, cách chạy test, kết quả test với dữ liệu mẫu, các mục mục 7 đã xử lý thế nào, và những câu hỏi còn cần người dùng xác nhận. Đính kèm 3 file xuất sinh từ dữ liệu mẫu để người dùng đối chiếu bằng mắt với mẫu gốc.
