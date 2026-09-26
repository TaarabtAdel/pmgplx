# Công cụ chuyển ảnh JP2 (bảng tên học viên)

File XML DKKH từ cổng GPLX nhúng ảnh chân dung định dạng **JPEG2000 (JP2)**.
PHP và trình duyệt không đọc trực tiếp — app dùng `opj_decompress` (OpenJPEG) để chuyển sang PNG.

## Windows (triển khai chính)

1. Tải **OpenJPEG** bản Windows: https://github.com/uclouvain/openjpeg/releases  
   (file zip, ví dụ `openjpeg-x.x.x-windows-x64.zip`)
2. Giải nén, copy **`opj_decompress.exe`** vào thư mục này:

```
laravel/bin/opj_decompress.exe
```

3. Không cần cài thêm PHP extension. Không cần thêm vào PATH nếu đặt đúng file trên.

Tuỳ chọn trong `.env` nếu đặt ở chỗ khác:

```
JP2_DECOMPRESS_BIN=D:\tools\opj_decompress.exe
```

## Linux / Docker

Copy hoặc liên kết binary vào `bin/opj_decompress`, hoặc cài gói `libopenjp2-tools` (có sẵn trong Docker).

## Kiểm tra

```bat
laravel\bin\opj_decompress.exe -h
```

---

# Clippit — gộp nhiều DOCX (xuất tổng biên bản)

Sau khi xuất từng file vào `storage/.../tung-file/`, có thể gộp bằng **Clippit** (Open XML DocumentBuilder, **không cần Microsoft Word**).

## Tải `clippit.exe` (Windows x64, ~41 MB)

Trên server Windows (cần [Node/npm](https://nodejs.org/) để tải một lần):

```bat
cd F:\pmgplx\bin
powershell -ExecutionPolicy Bypass -File download-clippit.ps1
```

Hoặc trên Mac (copy file `.exe` sang server):

```bash
cd laravel/bin && bash download-clippit.sh
```

File đích: `laravel/bin/clippit.exe`

## Thử gộp thủ công

1. Vào thư mục phiên xuất (có `tung-file/`):

```bat
cd F:\pmgplx\storage\app\temp\bien-ban-tong\{uuid}
```

2. Tạo `word-build.json` (xem mẫu `bin/merge-clippit-example.json`), liệt kê đủ file trong `entries`.

3. Chạy:

```bat
F:\pmgplx\bin\clippit.exe word build run word-build.json --output bien-ban-tong.docx --force
```

4. Mở `bien-ban-tong.docx` bằng Word trên máy bạn.

Kiểm tra cài đặt:

```bat
laravel\bin\clippit.exe --version
```

Tuỳ chọn `.env` (khi tích hợp vào Laravel):

```
CLIPPIT_BIN=F:\pmgplx\bin\clippit.exe
```
