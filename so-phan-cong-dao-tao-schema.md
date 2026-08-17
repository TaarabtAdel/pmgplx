# Đặc tả DB: Sổ Phân Công Đào Tạo (Giáo Viên - Xe Tập Lái)

## Bối cảnh
Bổ sung tính năng quản lý "Sổ phân công giáo viên, xe tập lái tham gia giảng dạy" cho từng khoá đào tạo (VD: C1K04), dựa trên mẫu Word hiện có của trung tâm. Dữ liệu nguồn được import từ file `.docx` (mẫu số 5), có thể tái dùng pattern import Node.js/exceljs tương tự bảng `dbo.TienDoDaoTao` đã làm trước đó.

Database: SQL Server. Schema: `dbo`.

---

## 1. Bảng `dbo.GiaoVien`

Lưu danh sách giáo viên tham gia giảng dạy.

| Cột | Kiểu dữ liệu | Ràng buộc | Ghi chú |
|---|---|---|---|
| `Id` | `INT IDENTITY(1,1)` | `PRIMARY KEY` | Khoá nội bộ, dùng cho FK |
| `MaGV` | `NVARCHAR(20)` | `UNIQUE`, **NULL** | Mã liên kết với phần mềm khác (VD mã nhân sự bên hệ thống ngoài). Nullable vì có thể chưa có mã tại thời điểm nhập/import |
| `HoTen` | `NVARCHAR(100)` | `NOT NULL` | Họ tên đầy đủ |
| `LoaiGV` | `NVARCHAR(20)` | NULL | `GVLT` (giáo viên lý thuyết) / `GVTH` (giáo viên thực hành) — có thể để trống, suy ra từ phân công |
| `SoDienThoai` | `NVARCHAR(15)` | NULL | |
| `TrangThai` | `BIT` | `DEFAULT 1` | 1 = đang hoạt động, 0 = nghỉ |
| `GhiChu` | `NVARCHAR(255)` | NULL | |
| `NgayTao` | `DATETIME` | `DEFAULT GETDATE()` | |
| `NgayCapNhat` | `DATETIME` | NULL | |

**Index gợi ý:** `IX_GiaoVien_HoTen` trên `HoTen` (phục vụ tìm kiếm/import đối chiếu tên).

---

## 2. Bảng `dbo.XeTapLai`

Lưu danh sách xe tập lái.

| Cột | Kiểu dữ liệu | Ràng buộc | Ghi chú |
|---|---|---|---|
| `Id` | `INT IDENTITY(1,1)` | `PRIMARY KEY` | |
| `BienSo` | `NVARCHAR(20)` | `UNIQUE`, `NOT NULL` | VD: `74A-246.00` |
| `LoaiXe` | `NVARCHAR(20)` | NULL | VD: `Số sàn`, `Số tự động` — suy từ cột "Nội dung giảng dạy" = "Xe tự động" trong mẫu |
| `HangXe` | `NVARCHAR(50)` | NULL | |
| `TrangThai` | `BIT` | `DEFAULT 1` | 1 = đang sử dụng, 0 = ngừng sử dụng |
| `GhiChu` | `NVARCHAR(255)` | NULL | |
| `NgayTao` | `DATETIME` | `DEFAULT GETDATE()` | |
| `NgayCapNhat` | `DATETIME` | NULL | |

**Index gợi ý:** `IX_XeTapLai_BienSo` (đã có UNIQUE).

---

## 3. Bảng `dbo.KhoaDaoTao`

Tách riêng để quản lý khoá đào tạo (trước đây là text tự do trong `PhanCongDaoTao`).

| Cột | Kiểu dữ liệu | Ràng buộc | Ghi chú |
|---|---|---|---|
| `Id` | `INT IDENTITY(1,1)` | `PRIMARY KEY` | Khoá nội bộ, dùng cho FK |
| `MaKhoa` | `NVARCHAR(20)` | `UNIQUE`, **NULL** | Mã liên kết với phần mềm khác. Nullable vì có thể chưa gán mã tại thời điểm tạo |
| `TenKhoa` | `NVARCHAR(50)` | `NOT NULL` | VD: `C1K04` — tên/mã hiển thị của khoá trong sổ |
| `HangDaoTao` | `NVARCHAR(10)` | NULL | VD: `B1`, `B2`, `C1`... nếu cần tách riêng khỏi `TenKhoa` |
| `NgayKhaiGiang` | `DATE` | NULL | |
| `NgayBeGiang` | `DATE` | NULL | |
| `TrangThai` | `NVARCHAR(20)` | NULL | VD: `Đang đào tạo`, `Đã kết thúc` |
| `GhiChu` | `NVARCHAR(255)` | NULL | |
| `NgayTao` | `DATETIME` | `DEFAULT GETDATE()` | |
| `NgayCapNhat` | `DATETIME` | NULL | |

**Index gợi ý:** `IX_KhoaDaoTao_TenKhoa` trên `TenKhoa` (UNIQUE nếu tên khoá không trùng nhau).

---

## 4. Bảng `dbo.PhanCongDaoTao`

Bảng trung tâm, lưu từng dòng phân công (1 dòng = 1 giáo viên/xe được phân công trong 1 khoảng thời gian của 1 khoá).

| Cột | Kiểu dữ liệu | Ràng buộc | Ghi chú |
|---|---|---|---|
| `Id` | `INT IDENTITY(1,1)` | `PRIMARY KEY` | |
| `SoTT` | `INT` | NULL | Số thứ tự hiển thị trong sổ gốc (theo khoá), chỉ để tham chiếu, không dùng làm khoá |
| `GiaoVienId` | `INT` | `FK -> GiaoVien.Id`, NULL | **Nullable** vì có dòng chỉ có xe, không có GV (VD dòng "Xe tự động" trong mẫu) |
| `XeTapLaiId` | `INT` | `FK -> XeTapLai.Id`, NULL | **Nullable** vì có dòng chỉ có GV lý thuyết, không có xe (VD dòng "GVLT") |
| `KhoaDaoTaoId` | `INT` | `FK -> KhoaDaoTao.Id`, `NOT NULL` | |
| `TuNgay` | `DATE` | `NOT NULL` | Parse từ "Thời gian" (vế trái) |
| `DenNgay` | `DATE` | `NOT NULL` | Parse từ "Thời gian" (vế phải) |
| `NoiDungGiangDay` | `NVARCHAR(100)` | NULL | Nhập **text tự do**, VD: `GVLT (hướng dẫn tự học)`, `GVTH`, `Xe tự động` — không ép thành enum |
| `GhiChu` | `NVARCHAR(255)` | NULL | |
| `NgayTao` | `DATETIME` | `DEFAULT GETDATE()` | |
| `NgayCapNhat` | `DATETIME` | NULL | |

**Ràng buộc:**
```sql
CONSTRAINT CHK_PhanCong_ThoiGian CHECK (DenNgay >= TuNgay)
CONSTRAINT CHK_PhanCong_GVhoacXe CHECK (GiaoVienId IS NOT NULL OR XeTapLaiId IS NOT NULL)
```

**Index gợi ý:**
- `IX_PhanCong_KhoaDaoTaoId` trên `KhoaDaoTaoId` (lọc theo khoá, use case chính)
- `IX_PhanCong_GiaoVienId`, `IX_PhanCong_XeTapLaiId` (join ngược khi xem lịch sử 1 GV/1 xe, và phục vụ check overlap)

---

## Quan hệ (ERD tóm tắt)

```
GiaoVien (1) ──< PhanCongDaoTao >── (1) XeTapLai
                       │
                       (n) : 1
                       │
                  KhoaDaoTao
```

---

## Validate chồng chéo lịch (GV / Xe)

**Yêu cầu:** 1 giáo viên hoặc 1 xe không được xuất hiện ở 2 dòng phân công có khoảng `[TuNgay, DenNgay]` giao nhau — **bất kể khác khoá hay cùng khoá** (vì 1 người/1 xe vật lý chỉ ở 1 nơi tại 1 thời điểm).

Điều kiện 2 khoảng ngày giao nhau: `A.TuNgay <= B.DenNgay AND A.DenNgay >= B.TuNgay`

SQL Server không cho `CHECK CONSTRAINT` tham chiếu cross-row, nên phải dùng **Trigger** (hoặc validate ở tầng application/API trước khi ghi — khuyến nghị làm cả 2 lớp: FE báo lỗi sớm, trigger là lớp chặn cuối cùng đảm bảo toàn vẹn dữ liệu).

```sql
CREATE TRIGGER trg_PhanCongDaoTao_CheckOverlap
ON dbo.PhanCongDaoTao
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Check overlap theo Giáo viên
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN dbo.PhanCongDaoTao pc
            ON pc.GiaoVienId = i.GiaoVienId
            AND pc.Id <> i.Id
            AND i.GiaoVienId IS NOT NULL
            AND i.TuNgay <= pc.DenNgay
            AND i.DenNgay >= pc.TuNgay
    )
    BEGIN
        RAISERROR (N'Giáo viên đã có phân công trùng khoảng thời gian.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END

    -- Check overlap theo Xe tập lái
    IF EXISTS (
        SELECT 1
        FROM inserted i
        JOIN dbo.PhanCongDaoTao pc
            ON pc.XeTapLaiId = i.XeTapLaiId
            AND pc.Id <> i.Id
            AND i.XeTapLaiId IS NOT NULL
            AND i.TuNgay <= pc.DenNgay
            AND i.DenNgay >= pc.TuNgay
    )
    BEGIN
        RAISERROR (N'Xe tập lái đã có phân công trùng khoảng thời gian.', 16, 1);
        ROLLBACK TRANSACTION;
        RETURN;
    END
END
```

**Lưu ý cho Cursor khi code:**
- Áp dụng đúng cho cả `INSERT` lẫn `UPDATE` (VD sửa lại ngày của 1 dòng phân công cũ).
- Nên validate ở API layer trước (query kiểm tra overlap tương tự) để trả lỗi rõ ràng cho UI, tránh phải bắt lỗi trigger từ exception SQL.
- Khi import hàng loạt từ file mẫu (.docx/.xlsx), nếu dính lỗi overlap giữa các dòng trong cùng file → cần log rõ dòng nào bị conflict với dòng nào để người dùng sửa tay, không nên để import fail toàn bộ file mà không rõ nguyên nhân.

---

## Mapping từ file mẫu số 5 (.docx) sang bảng `PhanCongDaoTao`

| Cột trong Word | Field DB |
|---|---|
| Số TT | `SoTT` |
| Giáo viên | tra/tạo `GiaoVien.HoTen` → lấy `GiaoVienId` |
| Thời gian (`dd/mm/yyyy - dd/mm/yyyy`) | tách thành `TuNgay`, `DenNgay` |
| Khoá đào tạo | tra/tạo `KhoaDaoTao.TenKhoa` → lấy `KhoaDaoTaoId` |
| Biển số đăng ký xe tập lái | tra/tạo `XeTapLai.BienSo` → lấy `XeTapLaiId` |
| Nội dung giảng dạy | `NoiDungGiangDay` (text tự do) |

**Lưu ý khi viết import script:**
- Dòng có ô "Giáo viên" trống (VD dòng "Xe tự động") → `GiaoVienId = NULL`.
- Dòng có ô "Biển số" trống (VD dòng GVLT hướng dẫn tự học) → `XeTapLaiId = NULL`.
- Khi import, nên `UPSERT` theo `HoTen` (GiaoVien), `BienSo` (XeTapLai), `TenKhoa` (KhoaDaoTao) — tránh tạo trùng nếu 1 GV/xe/khoá xuất hiện ở nhiều dòng/nhiều file.
- Format ngày trong mẫu có thể lệch khoảng trắng quanh dấu `-` (VD: `"02/07/2026 -12/07/2026"` thiếu space bên phải) → cần `trim()` + regex linh hoạt khi split, không split cứng theo `" - "`.
- `MaGV` và `MaKhoa` **không có trong file .docx nguồn** → khi import để `NULL`, chỉ điền sau khi đối chiếu/liên kết thủ công với phần mềm ngoài (hoặc qua bước mapping riêng).
- Vì có trigger check overlap, import hàng loạt nên chạy tuần tự (không bulk insert bỏ qua trigger) hoặc validate trước bằng code rồi mới insert, để bắt đúng dòng lỗi thay vì để cả batch fail.
