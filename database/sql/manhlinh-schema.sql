-- Schema MANHLINH (tương thích SQL Server 2012+)
-- Chạy sau: USE [MANHLINH];

IF OBJECT_ID(N'dbo.PhanCongDaoTao', N'U') IS NOT NULL DROP TABLE [PhanCongDaoTao];
IF OBJECT_ID(N'dbo.TienDoDaoTao', N'U') IS NOT NULL DROP TABLE [TienDoDaoTao];
IF OBJECT_ID(N'dbo.KhoaDaoTao', N'U') IS NOT NULL DROP TABLE [KhoaDaoTao];
IF OBJECT_ID(N'dbo.XeTapLai', N'U') IS NOT NULL DROP TABLE [XeTapLai];
IF OBJECT_ID(N'dbo.GiaoVien', N'U') IS NOT NULL DROP TABLE [GiaoVien];
GO

CREATE TABLE [GiaoVien] (
    [Id] int IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [MaGV] nvarchar(20) NULL,
    [HoTen] nvarchar(100) NOT NULL,
    [LoaiGV] nvarchar(20) NULL,
    [SoDienThoai] nvarchar(15) NULL,
    [TrangThai] bit NOT NULL CONSTRAINT [DF_GiaoVien_TrangThai] DEFAULT (1),
    [GhiChu] nvarchar(255) NULL,
    [NgayTao] datetime NOT NULL CONSTRAINT [DF_GiaoVien_NgayTao] DEFAULT (GETDATE()),
    [NgayCapNhat] datetime NULL
);
CREATE INDEX [IX_GiaoVien_HoTen] ON [GiaoVien]([HoTen]);
CREATE UNIQUE INDEX [IX_GiaoVien_MaGV] ON [GiaoVien]([MaGV]) WHERE [MaGV] IS NOT NULL;
GO

CREATE TABLE [XeTapLai] (
    [Id] int IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [BienSo] nvarchar(20) NOT NULL,
    [LoaiXe] nvarchar(20) NULL,
    [HangXe] nvarchar(50) NULL,
    [TrangThai] bit NOT NULL CONSTRAINT [DF_XeTapLai_TrangThai] DEFAULT (1),
    [GhiChu] nvarchar(255) NULL,
    [NgayTao] datetime NOT NULL CONSTRAINT [DF_XeTapLai_NgayTao] DEFAULT (GETDATE()),
    [NgayCapNhat] datetime NULL,
    CONSTRAINT [UQ_XeTapLai_BienSo] UNIQUE ([BienSo])
);
GO

CREATE TABLE [KhoaDaoTao] (
    [Id] int IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [MaKhoa] nvarchar(20) NULL,
    [TenKhoa] nvarchar(50) NOT NULL,
    [HangDaoTao] nvarchar(10) NULL,
    [NgayKhaiGiang] date NULL,
    [NgayBeGiang] date NULL,
    [TrangThai] nvarchar(20) NULL,
    [GhiChu] nvarchar(255) NULL,
    [NgayTao] datetime NOT NULL CONSTRAINT [DF_KhoaDaoTao_NgayTao] DEFAULT (GETDATE()),
    [NgayCapNhat] datetime NULL,
    CONSTRAINT [IX_KhoaDaoTao_TenKhoa] UNIQUE ([TenKhoa])
);
CREATE UNIQUE INDEX [IX_KhoaDaoTao_MaKhoa] ON [KhoaDaoTao]([MaKhoa]) WHERE [MaKhoa] IS NOT NULL;
GO

CREATE TABLE [PhanCongDaoTao] (
    [Id] int IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [GiaoVienId] int NULL,
    [XeTapLaiId] int NULL,
    [KhoaDaoTaoId] int NOT NULL,
    [TuNgay] date NOT NULL,
    [DenNgay] date NOT NULL,
    [LoaiGiangDay] nvarchar(20) NULL,
    [NoiDungGiangDay] nvarchar(100) NULL,
    [GhiChu] nvarchar(255) NULL,
    [NgayTao] datetime NOT NULL CONSTRAINT [DF_PhanCong_NgayTao] DEFAULT (GETDATE()),
    [NgayCapNhat] datetime NULL,
    CONSTRAINT [FK_PhanCong_GiaoVien] FOREIGN KEY ([GiaoVienId]) REFERENCES [GiaoVien]([Id]),
    CONSTRAINT [FK_PhanCong_XeTapLai] FOREIGN KEY ([XeTapLaiId]) REFERENCES [XeTapLai]([Id]),
    CONSTRAINT [FK_PhanCong_KhoaDaoTao] FOREIGN KEY ([KhoaDaoTaoId]) REFERENCES [KhoaDaoTao]([Id]),
    CONSTRAINT [CHK_PhanCong_ThoiGian] CHECK ([DenNgay] >= [TuNgay]),
    CONSTRAINT [CHK_PhanCong_GVhoacXe] CHECK ([GiaoVienId] IS NOT NULL OR [XeTapLaiId] IS NOT NULL)
);
CREATE INDEX [IX_PhanCong_KhoaDaoTaoId] ON [PhanCongDaoTao]([KhoaDaoTaoId]);
CREATE INDEX [IX_PhanCong_GiaoVienId] ON [PhanCongDaoTao]([GiaoVienId]);
CREATE INDEX [IX_PhanCong_XeTapLaiId] ON [PhanCongDaoTao]([XeTapLaiId]);
GO

CREATE TABLE [TienDoDaoTao] (
    [Id] int IDENTITY(1,1) NOT NULL PRIMARY KEY,
    [MaKhoaLop] nvarchar(50) NOT NULL,
    [GiaoVienDay] nvarchar(100) NULL,
    [SoLuongHocVien] int NULL,
    [SoHocVienTotNghiep] int NULL,
    [NamHoc] smallint NULL,
    [ThangNam] nvarchar(20) NULL,
    [TuanThu] tinyint NULL,
    [TuNgay] date NULL,
    [DenNgay] date NULL,
    [KyHieu] nvarchar(10) NULL,
    [GhiChu] nvarchar(500) NULL
);
CREATE INDEX [IX_TienDoDaoTao_Lop] ON [TienDoDaoTao]([MaKhoaLop], [NamHoc]);
GO
