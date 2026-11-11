<?php

namespace App\Support\HeThong;

use App\Support\MenuQldt;
use Illuminate\Support\Str;

/**
 * Cây chức năng để phân quyền: Tab → Menu 1 → Menu 2 (tối đa 2 cấp dưới tab).
 * Dùng chung cho ma trận phân quyền, menu chính (layout) và middleware chặn route.
 *
 * Mỗi nút: khoa (vd. «pmgplx/lich/lich-xe»), title, route (đường dẫn của mục), active (mẫu tên route thuộc mục,
 * kiểu routeIs), divider (vạch ngăn phía trên), children. Quyền gán trên nút lá (Menu 1 không có con, hoặc Menu 2).
 * Tab QLĐT lấy từ config/menu_qldt.php; nhánh sâu hơn Menu 2 gộp vào Menu 2 (tích Menu 2 = cả nhánh).
 */
final class CayChucNang
{
    /** Tab QLĐT chèn giữa DAT và Hệ Thống (giữ thứ tự menu chính). */
    private const VI_TRI_QLDT = 'he-thong';

    /** @var list<array<string, mixed>>|null */
    private static ?array $cay = null;

    /** @var array<string, array<string, mixed>>|null khoá lá => nút */
    private static ?array $la = null;

    /** @return list<array<string, mixed>> */
    public static function cay(): array
    {
        if (self::$cay !== null) {
            return self::$cay;
        }

        $cay = [];
        foreach (self::tinh() as $tab) {
            if ($tab['khoa'] === self::VI_TRI_QLDT) {
                foreach (config('menu_qldt.tabs', []) as $tabQldt) {
                    $cay[] = self::tabQldt($tabQldt);
                }
            }
            $cay[] = self::chuanHoa($tab, '');
        }

        return self::$cay = $cay;
    }

    /**
     * Các nút lá (đơn vị gán quyền), kèm đường dẫn tên Tab / Menu 1 / Menu 2.
     *
     * @return array<string, array{khoa: string, title: string, active: list<string>, duong_dan: list<string>}>
     */
    public static function laNut(): array
    {
        if (self::$la !== null) {
            return self::$la;
        }

        $out = [];
        $walk = function (array $nodes, array $duongDan) use (&$walk, &$out): void {
            foreach ($nodes as $n) {
                $dd = [...$duongDan, $n['title']];
                if ($n['children'] !== []) {
                    $walk($n['children'], $dd);
                } elseif (count($dd) > 1) {
                    $out[$n['khoa']] = ['khoa' => $n['khoa'], 'title' => $n['title'], 'active' => $n['active'], 'duong_dan' => $dd];
                }
            }
        };
        $walk(self::cay(), []);

        return self::$la = $out;
    }

    /** @return list<string> */
    public static function tatCaKhoaLa(): array
    {
        return array_keys(self::laNut());
    }

    /**
     * Mọi mẫu route của nút và các nút con (để tô sáng menu đang mở).
     *
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    public static function mauRoute(array $node): array
    {
        $out = $node['active'];
        foreach ($node['children'] as $c) {
            $out = [...$out, ...self::mauRoute($c)];
        }

        return array_values(array_unique($out));
    }

    public static function slug(string $title): string
    {
        return Str::slug($title) ?: substr(md5($title), 0, 8);
    }

    /** @param  array<string, mixed>  $node */
    private static function chuanHoa(array $node, string $khoaCha): array
    {
        $khoa = ($khoaCha === '' ? '' : $khoaCha.'/').$node['khoa'];
        $children = array_map(fn (array $c) => self::chuanHoa($c, $khoa), $node['children'] ?? []);

        return [
            'khoa' => $khoa,
            'title' => $node['title'],
            'route' => $node['route'] ?? null,
            'active' => $node['active'] ?? [],
            'divider' => (bool) ($node['divider'] ?? false),
            'children' => $children,
        ];
    }

    /** Khoá phân quyền của một tab trong config/menu_qldt.php (vd. «QLĐT Ô TÔ» → «qldt-o-to»). */
    public static function khoaTabQldt(array $tab): string
    {
        $slug = self::slug($tab['title']);

        return str_starts_with($slug, 'qldt-') ? $slug : 'qldt-'.$slug;
    }

    /** @param  array<string, mixed>  $tab  một tab trong config/menu_qldt.php */
    private static function tabQldt(array $tab): array
    {
        $khoaTab = self::khoaTabQldt($tab);
        $menu1 = [];
        foreach ($tab['children'] ?? [] as $m1) {
            $khoa1 = $khoaTab.'/'.self::slug($m1['title']);
            $menu2 = [];
            foreach ($m1['children'] ?? [] as $m2) {
                $menu2[] = [
                    'khoa' => $khoa1.'/'.self::slug($m2['title']),
                    'title' => $m2['title'],
                    'route' => null,
                    'active' => self::mauRouteQldt($m2),
                    'divider' => false,
                    'children' => [],
                ];
            }
            $menu1[] = [
                'khoa' => $khoa1,
                'title' => $m1['title'],
                'route' => null,
                'active' => $menu2 === [] ? self::mauRouteQldt($m1) : [],
                'divider' => false,
                'children' => $menu2,
            ];
        }

        return ['khoa' => $khoaTab, 'title' => $tab['title'], 'route' => null, 'active' => [], 'divider' => false, 'children' => $menu1];
    }

    /**
     * Mẫu tên route của một mục QLĐT và mọi mục con: «active» nếu có; route «x.index» → «x.*»;
     * mục chỉ có «trang» → route trang trắng qldtoto.{slug}.
     *
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private static function mauRouteQldt(array $item): array
    {
        $out = [];
        if (! empty($item['children'])) {
            foreach ($item['children'] as $c) {
                $out = [...$out, ...self::mauRouteQldt($c)];
            }
        } elseif (! empty($item['active'])) {
            $out[] = (string) $item['active'];
        } elseif (! empty($item['route'])) {
            $r = (string) $item['route'];
            $out[] = str_ends_with($r, '.index') ? substr($r, 0, -6).'.*' : $r;
        } elseif (! empty($item['trang'])) {
            $out[] = MenuQldt::ROUTE_PREFIX.MenuQldt::tenRouteTrang((string) $item['trang']);
        }

        return array_values(array_unique($out));
    }

    /** Menu viết sẵn (PMGPLX, Phòng Đào Tạo, DAT, Hệ Thống) — giống menu chính trước đây. */
    private static function tinh(): array
    {
        $dat = fn (string $ten, string $title) => [
            'khoa' => $ten, 'title' => $title, 'route' => 'daotao.pdt.dat.'.$ten, 'active' => ['daotao.pdt.dat.'.$ten.'*'],
        ];
        $nhap = fn (string $ten, string $khoa, string $title) => [
            'khoa' => $khoa, 'title' => $title, 'route' => 'daotao.pdt.cong-cu-nhap.'.$ten, 'active' => ['daotao.pdt.cong-cu-nhap.'.$ten.'*'],
        ];

        return [
            [
                'khoa' => 'pmgplx', 'title' => 'PMGPLX', 'children' => [
                    ['khoa' => 'giao-vien', 'title' => 'Quản lý giáo viên', 'route' => 'pmgplx.dm.giao-vien.index', 'active' => ['pmgplx.dm.giao-vien.*']],
                    ['khoa' => 'hoc-vien', 'title' => 'Quản lý học viên', 'route' => 'pmgplx.dm.hoc-vien.index', 'active' => ['pmgplx.dm.hoc-vien.*']],
                    ['khoa' => 'hoc-vien-ban-cu', 'title' => 'Học viên (bản cũ)', 'route' => 'pmgplxold.dm.hoc-vien.index', 'active' => ['pmgplxold.*']],
                    ['khoa' => 'xe', 'title' => 'Quản lý xe', 'route' => 'pmgplx.dm.xe.index', 'active' => ['pmgplx.dm.xe.*']],
                    ['khoa' => 'khoa-hoc', 'title' => 'Quản lý khoá học', 'route' => 'pmgplx.dm.khoa-hoc.index', 'active' => ['pmgplx.dm.khoa-hoc.*']],
                    [
                        'khoa' => 'lich', 'title' => 'Lịch', 'divider' => true, 'children' => [
                            ['khoa' => 'lich-giao-vien', 'title' => 'Lịch làm việc giáo viên', 'route' => 'pmgplx.lich.gv.index',
                                'active' => ['pmgplx.lich.gv.index', 'pmgplx.lich.gv.destroy', 'pmgplx.lich.gv.destroy-theo-khoa']],
                            ['khoa' => 'lich-xe', 'title' => 'Lịch sử dụng xe tập lái', 'route' => 'pmgplx.lich.xe.index', 'active' => ['pmgplx.lich.xe.*']],
                            ['khoa' => 'do-trung', 'title' => 'Dò trùng lịch giáo viên', 'route' => 'pmgplx.lich.gv.do-trung', 'active' => ['pmgplx.lich.gv.do-trung']],
                            ['khoa' => 'do-trong', 'title' => 'Dò lịch trống giáo viên', 'route' => 'pmgplx.lich.gv.do-trong', 'active' => ['pmgplx.lich.gv.do-trong*']],
                            ['khoa' => 'them-ly-thuyet', 'title' => 'Thêm lịch lý thuyết', 'route' => 'pmgplx.lich.ly-thuyet.create', 'active' => ['pmgplx.lich.ly-thuyet.*'], 'divider' => true],
                            ['khoa' => 'them-thuc-hanh', 'title' => 'Thêm lịch thực hành', 'route' => 'pmgplx.lich.thuc-hanh.create', 'active' => ['pmgplx.lich.thuc-hanh.*']],
                            ['khoa' => 'nhap-file', 'title' => 'Nhập lịch từ file', 'route' => 'pmgplx.lich.nhap-file.create', 'active' => ['pmgplx.lich.nhap-file.*']],
                            ['khoa' => 'nhap-thay-the', 'title' => 'Nhập lịch thay thế', 'route' => 'pmgplx.lich.nhap-thay-the.create', 'active' => ['pmgplx.lich.nhap-thay-the.*']],
                        ],
                    ],
                ],
            ],
            [
                'khoa' => 'phong-dao-tao', 'title' => 'Phòng Đào Tạo', 'children' => [
                    [
                        'khoa' => 'bao-cao', 'title' => 'Báo cáo', 'children' => [
                            ['khoa' => 'luu-luong', 'title' => 'Báo cáo Lưu lượng đào tạo', 'route' => 'daotao.pdt.bc.luu-luong-dao-tao', 'active' => ['daotao.pdt.bc.luu-luong-dao-tao*']],
                            ['khoa' => 'tien-do', 'title' => 'Báo cáo Tiến độ đào tạo', 'route' => 'daotao.pdt.bc.tien-do-dao-tao', 'active' => ['daotao.pdt.bc.tien-do-dao-tao*']],
                            ['khoa' => 'thu-tien', 'title' => 'Quản lý thu tiền học viên', 'route' => 'daotao.pdt.bc.thu-tien-hoc-vien', 'active' => ['daotao.pdt.bc.thu-tien-hoc-vien*']],
                            ['khoa' => 'cap-nhat-gplx', 'title' => 'Quản lý cập nhật GPLX học viên', 'route' => 'daotao.pdt.bc.cap-nhat-gplx', 'active' => ['daotao.pdt.bc.cap-nhat-gplx*']],
                        ],
                    ],
                    [
                        'khoa' => 'cong-cu-nhap', 'title' => 'Công cụ nhập', 'children' => [
                            $nhap('nhap-file-tien-do-dao-tao', 'tien-do', 'Nhập file tiến độ đào tạo'),
                            $nhap('nhap-file-sdt-sale-hoc-vien', 'sdt-sale-hv', 'Nhập file SDT / Sale học viên'),
                            $nhap('nhap-file-so-phan-cong-giao-vien', 'so-phan-cong', 'Nhập file sổ phân công giáo viên'),
                            $nhap('nhap-file-in-bang-ten', 'in-bang-ten', 'Nhập file in bảng tên'),
                            $nhap('nhap-ket-qua-dao-tao', 'ket-qua-dao-tao', 'Nhập kết quả đào tạo'),
                            $nhap('nhap-ket-qua-sat-hach', 'ket-qua-sat-hach', 'Nhập kết quả sát hạch'),
                            $nhap('nhap-xml-bien-ban', 'xml-bien-ban', 'Nhập XML xuất biên bản'),
                        ],
                    ],
                    [
                        'khoa' => 'phan-cong', 'title' => 'Phân Công Đào Tạo', 'children' => [
                            ['khoa' => 'giao-vien', 'title' => 'Phân công giáo viên', 'route' => 'daotao.pdt.phan-cong-dao-tao.danh-sach', 'active' => ['daotao.pdt.phan-cong-dao-tao.danh-sach']],
                            ['khoa' => 'hoc-vien', 'title' => 'Phân công học viên', 'route' => 'daotao.pdt.dat.phan-cong-hoc-vien', 'active' => ['daotao.pdt.dat.phan-cong-hoc-vien*']],
                            ['khoa' => 'tu-lich', 'title' => 'Phân công từ lịch PMGPLX', 'route' => 'daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich', 'active' => ['daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich']],
                            ['khoa' => 'tong-hop', 'title' => 'Tổng hợp phân công (khoá · xe)', 'route' => 'daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop', 'active' => ['daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop*']],
                        ],
                    ],
                ],
            ],
            [
                'khoa' => 'dat', 'title' => 'DAT', 'children' => [
                    ['khoa' => 'tong-hop', 'title' => 'Tổng hợp & theo dõi', 'children' => [
                        $dat('tong-hop-hoc-vien', 'Tổng hợp học viên'),
                        $dat('theo-doi', 'Theo dõi DAT'),
                    ]],
                    ['khoa' => 'phien', 'title' => 'Quản lý phiên', 'children' => [
                        $dat('quan-ly-phien', 'Chi tiết phiên'),
                        $dat('phan-loai-phien', 'Phân loại phiên'),
                    ]],
                    ['khoa' => 'do-phien', 'title' => 'Dò phiên', 'children' => [
                        $dat('do-phien-tuyen-duong', 'Dò tuyến đường'),
                        $dat('do-phien-anh', 'Dò ảnh'),
                        $dat('do-phien-lich-xe', 'Dò phiên với lịch xe'),
                    ]],
                    ['khoa' => 'phan-cong', 'title' => 'Phân công', 'children' => [
                        $dat('giao-vien-day-thay', 'Giáo viên dạy thay'),
                        $dat('xe-day-thay', 'Xe dạy thay'),
                        $dat('nhap-phan-cong-hoc-vien', 'Nhập phân công học viên'),
                    ]],
                    ['khoa' => 'nhap-lieu', 'title' => 'Nhập liệu', 'children' => [
                        $dat('nhap-du-lieu-phien', 'Nhập dữ liệu phiên'),
                        $dat('nhap-ket-qua-cuc', 'Nhập kết quả cục'),
                    ]],
                    ['khoa' => 'cau-hinh', 'title' => 'Cấu hình', 'children' => [
                        $dat('dieu-kien-canh-bao', 'Điều kiện cảnh báo'),
                        $dat('dieu-kien-do-phien', 'Điều kiện dò phiên'),
                        $dat('dieu-kien-dat', 'Điều kiện đạt'),
                    ]],
                ],
            ],
            [
                'khoa' => 'he-thong', 'title' => 'Hệ Thống', 'children' => [
                    ['khoa' => 'nguoi-dung', 'title' => 'Người dùng', 'route' => 'hethong.nguoi-dung.index', 'active' => ['hethong.nguoi-dung.*']],
                    ['khoa' => 'vai-tro', 'title' => 'Vai trò', 'children' => [
                        ['khoa' => 'danh-sach', 'title' => 'Danh sách vai trò', 'route' => 'hethong.vai-tro.index', 'active' => ['hethong.vai-tro.*']],
                        ['khoa' => 'phan-quyen', 'title' => 'Phân quyền', 'route' => 'hethong.phan-quyen.index', 'active' => ['hethong.phan-quyen.*']],
                    ]],
                    ['khoa' => 'cau-hinh-bai-day', 'title' => 'Cấu hình bài dạy theo hạng', 'route' => 'qldtoto.cau-hinh-bai-day.index', 'active' => ['qldtoto.cau-hinh-bai-day.*']],
                ],
            ],
        ];
    }
}
