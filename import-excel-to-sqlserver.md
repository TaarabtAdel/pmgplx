# Import "Tiến độ đào tạo" Excel → SQL Server

## 1. Bối cảnh

File nguồn: `TIE__N_ĐO___ĐA_O_TA_O.xlsx` (Trung tâm GDNN Mạnh Linh).
Có 3 sheet: `2025`, `2026`, `2026 (2)` — cùng cấu trúc, khác dữ liệu/năm.

Mỗi sheet là 1 bảng theo dõi tiến độ đào tạo lái xe: mỗi lớp có nhiều
tuần (nhóm theo tháng), mỗi ô tuần chứa ký hiệu giai đoạn (H/T/Đ/•,
có thể ghép: HT, TĐ, ĐT, "ĐT •"...).

## 2. Cấu trúc file Excel (theo từng sheet)

- **Header cột (dòng 5–8)**:
  - Dòng 5: `B5` = Số TT, `C5` = Khóa - lớp, `D5` = Giáo viên dạy,
    `E5` = Số lượng học viên, sau đó mỗi 4 cột là 1 tháng
    (`F5`="Tháng 1/2026", `J5`="Tháng 2/2026", `N5`="Tháng 3/2026"...
    đến `BJ5`="Tháng 03/2027"), cuối cùng `BN5` = Số học viên tốt
    nghiệp, `BO5` = Ghi chú.
  - Dòng 6: số thứ tự tuần trong tháng (1,2,3,4) lặp lại theo từng
    nhóm 4 cột.
  - Dòng 7: ngày bắt đầu tuần (vd 1, 8, 16, 24).
  - Dòng 8: ngày kết thúc tuần (vd 7, 15, 23, 31 — cuối tháng có thể
    là 28/29/30/31 tùy tháng).
- **Dữ liệu (từ dòng 9 trở đi, mỗi dòng 1 lớp)**:
  - `B` = Số TT, `C` = Mã khóa-lớp (vd `B01K31`), `D` = Giáo viên dạy
    (số/mã), `E` = Số lượng học viên.
  - Các cột tuần (`F..BM`) = ký hiệu giai đoạn của lớp đó trong tuần
    tương ứng. Có thể rỗng (chưa tới/đã qua giai đoạn) hoặc chứa 1
    trong các ký hiệu: `H`, `T`, `Đ`, `•` hoặc ghép (`HT`, `TĐ`, `ĐT`,
    `ĐT •`, có khi có khoảng trắng thừa).
  - `BN` = Số học viên tốt nghiệp, `BO` = Ghi chú.
  - Dòng cuối mỗi sheet có ghi chú/chữ ký, KHÔNG phải dữ liệu — bỏ
    qua các dòng không có giá trị ở cột `C` (Khóa - lớp).

## 3. Cấu trúc bảng đích trong SQL Server

```sql
CREATE TABLE dbo.TienDoDaoTao
(
    Id                  INT IDENTITY(1,1) PRIMARY KEY,
    SoTT                INT             NULL,
    MaKhoaLop           NVARCHAR(50)    NOT NULL,
    GiaoVienDay         NVARCHAR(100)   NULL,
    SoLuongHocVien      INT             NULL,
    SoHocVienTotNghiep  INT             NULL,
    NamHoc              SMALLINT        NULL,   -- năm của sheet (2025, 2026...)

    ThangNam            NVARCHAR(20)    NULL,   -- vd: 'Tháng 1/2026'
    TuanThu             TINYINT         NULL,   -- 1,2,3,4
    TuNgayDenNgay       NVARCHAR(30)    NULL,   -- vd: '01/01 - 07/01/2026'

    KyHieu              NVARCHAR(10)    NULL,   -- 'H','T','Đ','HT','TĐ','ĐT','ĐT •'...
    GhiChu              NVARCHAR(500)   NULL
);

CREATE INDEX IX_TienDoDaoTao_Lop ON dbo.TienDoDaoTao (MaKhoaLop, NamHoc);
```

Mỗi dòng trong bảng = 1 lớp × 1 tuần (pivot từ dạng ngang trong Excel
sang dạng dọc). Ô nào rỗng trong Excel thì `KyHieu = NULL` — vẫn tạo
dòng (để giữ đủ mốc thời gian của lớp), hoặc bỏ qua tùy bạn — khuyến
nghị **giữ lại** để sau này biết lớp có bao nhiêu tuần tổng cộng.

`SoHocVienTotNghiep` và `GhiChu` (từ cột `BN`, `BO`) chỉ cần lưu 1 lần
cho lớp đó — có thể lặp lại ở mọi dòng tuần của lớp cho đơn giản (denormalized), hoặc chỉ set ở dòng cuối cùng — chọn 1 cách cho nhất quán,
khuyến nghị: **lặp lại ở mọi dòng** (dễ query, khỏi phải JOIN thêm).

## 4. Yêu cầu công việc (giao Cursor)

Viết script (Node.js, dùng `exceljs` hoặc `xlsx`, và `mssql` để kết
nối SQL Server) để:

1. Đọc từng sheet trong file `TIE__N_ĐO___ĐA_O_TA_O.xlsx`.
2. Với mỗi sheet, xác định `NamHoc` (lấy từ tên sheet, vd `"2026"` →
   2026; sheet `"2026 (2)"` → vẫn map về `NamHoc = 2026` nhưng đây là
   dữ liệu khác — coi là 1 batch riêng, không merge đè lên sheet
   `"2026"`. Có thể thêm cách phân biệt bằng cách giữ nguyên tên sheet
   gốc nếu cần, hoặc hỏi lại yêu cầu nghiệp vụ nếu 2 sheet cùng năm
   được kỳ vọng là 2 danh sách lớp độc lập).
3. Đọc dòng 5–8 để dựng map: với mỗi cột từ `F` đến `BM`, xác định
   `ThangNam` (lấy từ dòng 5, cột đầu mỗi nhóm 4 cột — merge cell nên
   cần forward-fill sang các cột tiếp theo trong cùng nhóm),
   `TuanThu` (dòng 6), ngày bắt đầu (dòng 7), ngày kết thúc (dòng 8) →
   ghép thành chuỗi `TuNgayDenNgay` dạng `"DD/MM - DD/MM/YYYY"`.
4. Từ dòng 9 trở đi, với mỗi dòng có giá trị ở cột `C` (Khóa - lớp):
   - Lấy `SoTT` (cột B), `MaKhoaLop` (cột C), `GiaoVienDay` (cột D),
     `SoLuongHocVien` (cột E), `SoHocVienTotNghiep` (cột BN),
     `GhiChu` (cột BO).
   - Với mỗi cột tuần `F..BM`: lấy giá trị ô làm `KyHieu` (trim
     khoảng trắng thừa, giữ nguyên dấu `•` nếu có), sinh 1 record kết
     hợp với `ThangNam`/`TuanThu`/`TuNgayDenNgay` tương ứng cột đó.
   - Bỏ qua các dòng cuối sheet không có `MaKhoaLop` (ghi chú, chữ
     ký...).
5. Insert toàn bộ record vào bảng `dbo.TienDoDaoTao` (batch insert,
   dùng transaction).
6. Sau khi import xong, chạy query đếm số dòng theo `MaKhoaLop,
   NamHoc` để đối chiếu: mỗi lớp phải có đúng số cột tuần tồn tại
   trong sheet đó (thường 60 tuần / sheet 2026, 2025 tùy số tháng có
   trong file).

## 5. Kết nối SQL Server

Dùng package `mssql`, connection string dạng:

```
Server=<host>,1433;Database=DaoTaoLaiXe;User Id=<user>;Password=<pass>;Encrypt=true;TrustServerCertificate=true
```

## 6. Lưu ý xử lý dữ liệu

- Ký hiệu ô có thể có khoảng trắng thừa (vd `'T '`, `'T •'`, `'T•'`)
  → chuẩn hóa: `trim()`, có thể chuẩn hóa dấu cách trước `•` cho nhất
  quán (tùy quyết định — giữ nguyên hoặc chuẩn hóa về 1 format).
- Cột `D` (Giáo viên dạy) trong file là số (mã giáo viên dạng số),
  lưu nguyên dạng text vào `GiaoVienDay`.
- Merge cell ở dòng 5 (tên tháng) cần forward-fill khi đọc bằng
  exceljs/xlsx vì chỉ ô đầu nhóm có giá trị.
