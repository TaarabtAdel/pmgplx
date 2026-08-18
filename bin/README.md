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
