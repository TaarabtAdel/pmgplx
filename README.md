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

File `.env` (connection `sqlsrv_manhlinh` → DB **MANHLINH**):

```env
DB_HOST=db
DB_PORT=1433
DB_USERNAME=sa
DB_PASSWORD=YourPassword123!
DB_DATABASE_3=MANHLINH
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

## Backup / restore MANHLINH

File backup nằm trên máy host: `khgplx/sqlserver-backup/` (mount vào container SQL Server).

**Backup:**

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C \
  -Q "BACKUP DATABASE MANHLINH TO DISK = N'/var/opt/mssql/backup/MANHLINH.bak' WITH FORMAT, INIT, NAME = N'MANHLINH-Full'"
```

**Restore:**

```bash
docker compose exec db /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "YourPassword123!" -C \
  -Q "RESTORE DATABASE MANHLINH FROM DISK = N'/var/opt/mssql/backup/MANHLINH.bak' WITH REPLACE"
```

Sau restore, chạy lại migration nếu code mới hơn DB.

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
