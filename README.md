# KHGPLX — Laravel

Ứng dụng quản lý GPLX. Chạy bằng Docker từ thư mục **`khgplx`** (cùng cấp với `docker-compose.yml`).

## Khởi động

```bash
cd ..   # vào thư mục khgplx
docker compose up -d --build
docker compose run --rm app composer install
docker compose exec app php artisan key:generate
```

App: http://localhost:8080

## Cấu hình DB

File `.env` — hai database thường dùng trên Docker:

```env
DB_HOST=db
DB_PORT=1433
DB_USERNAME=sa
DB_PASSWORD=YourPassword123!

DB_DATABASE=GPLX_BAN_MOI          # PMGPLX (mặc định sqlsrv): lịch, danh mục, KhoaHoc, …
DB_DATABASE_2=GPLX_BAN_CU         # (tùy chọn) DB cũ để so sánh
DB_DATABASE_3=MANHLINH            # sqlsrv_manhlinh: DAT, phân công, tiến độ, …
```

Tạo database (lần đầu):

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C \
  -Q "CREATE DATABASE MANHLINH"
```

Migration bảng MANHLINH:

```bash
docker compose exec app php artisan migrate \
  --database=sqlsrv_manhlinh \
  --path=database/migrations/manhlinh
```

Kiểm tra trạng thái migration:

```bash
docker compose exec app php artisan migrate:status \
  --database=sqlsrv_manhlinh \
  --path=database/migrations/manhlinh
```

**Reset sạch bảng MANHLINH** (xóa hết bảng, rồi migrate lại từ đầu — mất toàn bộ dữ liệu):

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C -d MANHLINH -Q "
SET NOCOUNT ON;
DECLARE @sql NVARCHAR(MAX) = N'';
SELECT @sql += N'ALTER TABLE [' + OBJECT_SCHEMA_NAME(parent_object_id) + N'].[' + OBJECT_NAME(parent_object_id) + N'] DROP CONSTRAINT [' + name + N'];' + CHAR(13)
FROM sys.foreign_keys;
EXEC sp_executesql @sql;
SET @sql = N'';
SELECT @sql += N'DROP TABLE [' + TABLE_SCHEMA + N'].[' + TABLE_NAME + N'];' + CHAR(13)
FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE';
EXEC sp_executesql @sql;"

docker compose exec app php artisan migrate \
  --database=sqlsrv_manhlinh \
  --path=database/migrations/manhlinh
```

Sau migrate sạch, DB có các bảng nghiệp vụ DAT + `migrations`, gồm: `GiaoVien`, `XeTapLai`, `KhoaDaoTao`, `PhanCongDaoTao`, `TienDoDaoTao`, `DatDSPhien`, `DatPhanLoaiPhien`, `DatDSPhienPhanLoai`, `DatDieuKienCanhBao`, `DatDieuKienDat`, `DatPhanCongHocVien`, …

## Backup / restore `.bak` (Docker)

Chạy mọi lệnh từ thư mục **`khgplx`** (có `docker-compose.yml`).

**Mount file backup vào container:**

| Thư mục host | Trong container |
|---|---|
| `laravel/database/dumps/` | `/var/opt/mssql/dumps` |
| `sqlserver-backup/` | `/var/opt/mssql/backup` |

Backup từ Windows (SQL Server 2012) thường đặt tại `sqlserver-backup/`. Có thể copy sang `laravel/database/dumps/` nếu muốn.

**Lưu ý:** Restore backup Windows lên SQL Server trong Docker **bắt buộc** dùng `MOVE` (đường dẫn `.mdf`/`.ldf` trong `.bak` trỏ `C:\Program Files\...`). Nếu đổi file `.bak` mới, xem tên logical file:

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C \
  -Q "RESTORE FILELISTONLY FROM DISK = N'/var/opt/mssql/dumps/TEN_FILE.bak'"
```

**Sau restore từ `.bak` đầy đủ:** không chạy `migrate` MANHLINH (DB đã có bảng + dữ liệu). Chỉ dùng migrate khi [reset sạch bảng](#reset-sạch-bảng-manhlinh) ở trên.

### Restore MANHLINH → `DB_DATABASE_3`

File mẫu: `sqlserver-backup/MANHLINH.bak` (logical: `MANHLINH`, `MANHLINH_log`).

```bash
# Copy vào dumps (nếu file đang ở sqlserver-backup)
cp sqlserver-backup/MANHLINH.bak laravel/database/dumps/

docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C -Q "
IF DB_ID(N'MANHLINH') IS NOT NULL BEGIN
  ALTER DATABASE MANHLINH SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
  DROP DATABASE MANHLINH;
END
RESTORE DATABASE MANHLINH FROM DISK = N'/var/opt/mssql/dumps/MANHLINH.bak' WITH REPLACE,
  MOVE N'MANHLINH'     TO N'/var/opt/mssql/data/MANHLINH.mdf',
  MOVE N'MANHLINH_log' TO N'/var/opt/mssql/data/MANHLINH_log.ldf';"
```

### Restore GPLX_BAN_MOI → `DB_DATABASE`

File mẫu: `sqlserver-backup/GPLX_CSDL_CSDT_202608140324PM.bak` (logical: `GPLX_CDB_CSDT_25122025`, `GPLX_CDB_CSDT_25122025_log`).

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C -Q "
IF DB_ID(N'GPLX_BAN_MOI') IS NOT NULL BEGIN
  ALTER DATABASE GPLX_BAN_MOI SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
  DROP DATABASE GPLX_BAN_MOI;
END
RESTORE DATABASE GPLX_BAN_MOI FROM DISK = N'/var/opt/mssql/backup/GPLX_CSDL_CSDT_202608140324PM.bak' WITH REPLACE,
  MOVE N'GPLX_CDB_CSDT_25122025'     TO N'/var/opt/mssql/data/GPLX_BAN_MOI.mdf',
  MOVE N'GPLX_CDB_CSDT_25122025_log' TO N'/var/opt/mssql/data/GPLX_BAN_MOI_log.ldf';"
```

### Restore cả hai DB (một lần)

```bash
cp sqlserver-backup/MANHLINH.bak laravel/database/dumps/

docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C -Q "
IF DB_ID(N'MANHLINH') IS NOT NULL BEGIN ALTER DATABASE MANHLINH SET SINGLE_USER WITH ROLLBACK IMMEDIATE; DROP DATABASE MANHLINH; END
RESTORE DATABASE MANHLINH FROM DISK = N'/var/opt/mssql/dumps/MANHLINH.bak' WITH REPLACE,
  MOVE N'MANHLINH' TO N'/var/opt/mssql/data/MANHLINH.mdf',
  MOVE N'MANHLINH_log' TO N'/var/opt/mssql/data/MANHLINH_log.ldf';
IF DB_ID(N'GPLX_BAN_MOI') IS NOT NULL BEGIN ALTER DATABASE GPLX_BAN_MOI SET SINGLE_USER WITH ROLLBACK IMMEDIATE; DROP DATABASE GPLX_BAN_MOI; END
RESTORE DATABASE GPLX_BAN_MOI FROM DISK = N'/var/opt/mssql/backup/GPLX_CSDL_CSDT_202608140324PM.bak' WITH REPLACE,
  MOVE N'GPLX_CDB_CSDT_25122025' TO N'/var/opt/mssql/data/GPLX_BAN_MOI.mdf',
  MOVE N'GPLX_CDB_CSDT_25122025_log' TO N'/var/opt/mssql/data/GPLX_BAN_MOI_log.ldf';"
```

**Backup MANHLINH (tạo file mới):**

```bash
BACKUP_FILE="MANHLINH_$(date +%Y%m%d_%H%M%S).bak"

docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C \
  -Q "BACKUP DATABASE MANHLINH TO DISK = N'/var/opt/mssql/dumps/${BACKUP_FILE}' WITH FORMAT, INIT, NAME = N'MANHLINH-Full'"

echo "File: laravel/database/dumps/${BACKUP_FILE}"
```

## Xuất file SQL (CREATE + INSERT) — tương thích SQL Server 2012

Giống `mysqldump`: gồm tạo bảng + chèn dữ liệu. **Giả định DB `MANHLINH` đã tạo sẵn** (phù hợp SQL Server 2012 trên Windows). File không DROP database.

**Xuất (Mac / Docker):**

```bash
docker compose exec app php artisan manhlinh:dump-sql
```

File ra: `laravel/database/dumps/MANHLINH.sql` (commit lên git được).

Xuất kèm DROP + CREATE DATABASE (reset sạch trên Mac):

```bash
docker compose exec app php artisan manhlinh:dump-sql --fresh-db
```

**Import trên Windows (SQL Server 2012):**

1. Tạo DB rỗng (nếu chưa có): `CREATE DATABASE MANHLINH`
2. SSMS → mở `laravel/database/dumps/MANHLINH.sql` → Execute  
   Hoặc sqlcmd:

```powershell
sqlcmd -S localhost -U sa -P "..." -C -i "C:\path\to\khgplx\laravel\database\dumps\MANHLINH.sql"
```

2. Sửa `laravel\.env` trỏ DB `MANHLINH` trên Win → chạy app.
