@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Dò tuyến đường')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .dat-do-tuyen-duong-table .cell-sub {
        display: block;
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.15rem;
    }
    .dat-do-tuyen-duong-table .cell-sub.d-inline {
        display: inline;
        margin-top: 0;
        margin-left: 0.25rem;
    }
    .dat-do-tuyen-duong-table .col-thong-tin,
    .dat-do-tuyen-duong-table .col-thoi-gian {
        white-space: normal;
        vertical-align: top;
    }
    .dat-do-tuyen-duong-table .col-ban-do {
        width: 55%;
        min-width: 22rem;
        vertical-align: top;
    }
    .dat-gps-map {
        width: 100%;
        height: 220px;
        border-radius: 4px;
        background: #eef2f6;
        z-index: 0;
    }
    .dat-gps-map-wrap .dat-gps-map-placeholder.is-loading {
        background: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 4px;
    }
    .dat-gps-popup {
        min-width: 160px;
        line-height: 1.5;
        font-size: 0.85rem;
    }
    .dat-gps-popup strong {
        display: inline-block;
        min-width: 64px;
    }
    .dat-gps-end-label {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #1976d2;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        color: #1f4e79;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 2px 8px;
        white-space: nowrap;
    }
    .dat-gps-end-label.dat-gps-end-label--start {
        border-color: #2e7d32;
        color: #2e7d32;
    }
    .dat-gps-end-label.dat-gps-end-label--end {
        border-color: #c62828;
        color: #c62828;
    }
    .leaflet-tooltip.dat-gps-end-label {
        margin-top: -6px;
    }
</style>
@endpush

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Dò tuyến đường</span>
            <div class="mt-1 mt-md-0">
                <button type="button" class="btn btn-sm btn-outline-secondary mr-1" data-toggle="modal" data-target="#modalXeOnlineBearer">
                    Cấu hình Bearer
                </button>
                <a href="{{ route('daotao.pdt.dat.do-phien-anh') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Dò ảnh
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Chi tiết phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Dò phiên với lịch xe
                </a>
                @if ($filters['ma_khoa_hoc'] !== '')
                    <a href="{{ route('pmgplx.lich.xe.index', ['ma_kh' => $filters['ma_khoa_hoc']]) }}"
                       class="btn btn-sm btn-navy" target="_blank" rel="noopener">
                        Lịch xe tập (khóa này)
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Gọi API <strong>XeOnline</strong> lấy <code>ListCoordinate</code> và vẽ tuyến GPS trên bản đồ OpenStreetMap.
                    API: <code>{{ $xeOnlineApiBaseUrl }}/api/XeOnline</code>
                </p>
                <p class="mb-0">
                    @if ($hasXeOnlineBearer)
                        Bearer: <strong class="text-success">đã lưu</strong>
                        @if ($xeOnlineBearerPreview !== '')
                            (<code>{{ $xeOnlineBearerPreview }}</code>)
                        @endif
                    @else
                        Bearer: <strong class="text-danger">chưa cấu hình</strong> — bấm <strong>Cấu hình Bearer</strong> trước khi dò.
                    @endif
                    · Chọn <strong>mã khóa học</strong> → <strong>Tiến Hành Dò</strong>.
                </p>
            </div>

            <form method="GET" action="{{ route('daotao.pdt.dat.do-phien-tuyen-duong') }}" class="mb-3" id="datDoTuyenDuongFilterForm">
                @include('DaoTao.dat.partials.phien-filter', [
                    'resetRoute' => route('daotao.pdt.dat.do-phien-tuyen-duong'),
                    'requireKhoaHoc' => true,
                    'hideDatFilter' => true,
                ])
            </form>

            @if ($items !== null)
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="text-muted small">
                        <strong>{{ number_format($items->total()) }}</strong> phiên theo bộ lọc
                        · Khóa: <strong>{{ $selectedKhoaHocLabel ?: $filters['ma_khoa_hoc'] }}</strong>
                    </span>
                    @if ($items->count() > 0)
                        <button type="button" class="btn btn-sm btn-navy mt-2 mt-md-0" id="btnTienHanhDoTuyenDuong">
                            Tiến Hành Dò
                        </button>
                    @endif
                </div>

                @include('DaoTao.dat.partials.phien-ket-qua-bang-tuyen-duong')
            @else
                <p class="text-muted mb-0">Chọn mã khóa học để bắt đầu dò tuyến đường.</p>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalXeOnlineBearer" tabindex="-1" role="dialog" aria-labelledby="modalXeOnlineBearerLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('daotao.pdt.dat.do-phien-tuyen-duong.luu-bearer') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalXeOnlineBearerLabel">Cấu hình Bearer XeOnline</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Token lưu tạm trong <strong>phiên làm việc</strong> (session), không ghi DB.
                        Có thể dán cả chuỗi <code>Bearer eyJ...</code> hoặc chỉ phần token.
                    </p>
                    <div class="form-group mb-0">
                        <label for="input_xeonline_bearer" class="small font-weight-bold">Bearer token</label>
                        <textarea name="bearer_token" id="input_xeonline_bearer" rows="4"
                                  class="form-control form-control-sm font-monospace @error('bearer_token') is-invalid @enderror"
                                  placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">{{ old('bearer_token') }}</textarea>
                        @error('bearer_token')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($hasXeOnlineBearer)
                        <p class="small text-muted mt-2 mb-0">
                            Đang lưu: <code>{{ $xeOnlineBearerPreview }}</code>
                        </p>
                    @endif
                </div>
                <div class="modal-footer">
                    @if ($hasXeOnlineBearer)
                        <button type="submit" formaction="{{ route('daotao.pdt.dat.do-phien-tuyen-duong.xoa-bearer') }}"
                                formmethod="POST" class="btn btn-sm btn-outline-danger mr-auto"
                                onclick="return confirm('Xóa Bearer token khỏi phiên làm việc?');">
                            Xóa token
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-navy">Lưu Bearer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @include('DaoTao.dat.partials.phien-filter-scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        $('#filter_ma_khoa_hoc').on('change', function () {
            $('#datDoTuyenDuongFilterForm').submit();
        });

        @if ($errors->has('bearer_token'))
            $('#modalXeOnlineBearer').modal('show');
        @endif

        var hasXeOnlineBearer = @json($hasXeOnlineBearer);

        function datEscapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function datDestroyGpsMap($row) {
            var map = $row.data('leafletMap');
            if (map) {
                map.remove();
                $row.removeData('leafletMap');
            }
        }

        function datResetGpsRow($row) {
            datDestroyGpsMap($row);

            var $wrap = $row.find('.dat-gps-map-wrap');
            var $placeholder = $wrap.find('.dat-gps-map-placeholder');
            var $mapEl = $wrap.find('.dat-gps-map');

            $mapEl.addClass('d-none').empty();
            $placeholder
                .removeClass('is-loading text-danger')
                .addClass('text-muted')
                .text('Chưa dò GPS')
                .show();
            $row.find('.col-tuyen-tom-tat').empty();
        }

        function datSetGpsRowLoading($row) {
            datDestroyGpsMap($row);

            var $wrap = $row.find('.dat-gps-map-wrap');
            $wrap.find('.dat-gps-map').addClass('d-none').empty();
            $wrap.find('.dat-gps-map-placeholder')
                .addClass('is-loading text-muted')
                .removeClass('text-danger')
                .text('Đang dò GPS...')
                .show();
            $row.find('.col-tuyen-tom-tat').empty();
        }

        function datSetGpsRowError($row, message) {
            datDestroyGpsMap($row);

            var $wrap = $row.find('.dat-gps-map-wrap');
            $wrap.find('.dat-gps-map').addClass('d-none').empty();
            $wrap.find('.dat-gps-map-placeholder')
                .removeClass('is-loading text-muted')
                .addClass('text-danger')
                .text(message)
                .show();
        }

        function datLooksLikeCoordinates(text) {
            return /^-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?$/.test(String(text || '').trim());
        }

        function datCoordinateText(point) {
            if (point && point.Latitude != null && point.Longitude != null) {
                return Number(point.Latitude).toFixed(5) + ',' + Number(point.Longitude).toFixed(5);
            }

            if (point && point.StrPoint && datLooksLikeCoordinates(point.StrPoint)) {
                return String(point.StrPoint).trim();
            }

            return '—';
        }

        function datEndpointPlaceLabel(meta, which) {
            meta = meta || {};
            var direct = which === 'start' ? meta.ten_diem_dau : meta.ten_diem_cuoi;
            if (direct) {
                return String(direct);
            }

            var diem = which === 'start' ? meta.diem_dau : meta.diem_cuoi;
            if (diem && diem.ten) {
                return String(diem.ten);
            }

            var nominatim = meta.nominatim || {};
            var nomDiem = which === 'start' ? nominatim.diem_dau : nominatim.diem_cuoi;
            if (nomDiem) {
                if (nomDiem.label) {
                    return String(nomDiem.label);
                }
                if (nomDiem.display_name) {
                    return String(nomDiem.display_name).split(',')[0].trim();
                }
            }

            return '—';
        }

        function datRouteSummaryText(data, duLieu) {
            duLieu = duLieu || {};

            if (duLieu.ten_tuyen_trong_api) {
                return String(duLieu.ten_tuyen_trong_api);
            }

            if (duLieu.ten_tuyen_nominatim) {
                return String(duLieu.ten_tuyen_nominatim);
            }

            if (duLieu.ten_diem_dau && duLieu.ten_diem_cuoi) {
                if (duLieu.ten_diem_dau === duLieu.ten_diem_cuoi) {
                    return String(duLieu.ten_diem_dau);
                }

                return duLieu.ten_diem_dau + ' - ' + duLieu.ten_diem_cuoi;
            }

            if (duLieu.ten_diem_dau) {
                return String(duLieu.ten_diem_dau);
            }

            if (duLieu.ten_diem_cuoi) {
                return String(duLieu.ten_diem_cuoi);
            }

            if (data && data.tuyen_duong && data.tuyen_duong !== '—') {
                return String(data.tuyen_duong);
            }

            return duLieu.tom_tat_gps || '—';
        }

        function datPointLabel(point) {
            if (point && point.StrPoint && !datLooksLikeCoordinates(point.StrPoint)) {
                return String(point.StrPoint);
            }

            return datCoordinateText(point);
        }

        function datFitMapBounds(map, bounds) {
            map.fitBounds(bounds, { padding: [16, 16] });

            var zoom = map.getZoom();
            var zoomOut = Math.log(1.5) / Math.log(2);
            map.setZoom(Math.max(map.getMinZoom(), zoom - zoomOut));
        }

        function datAddNamedEndpointMarker(map, point, label, typeClass) {
            var latLng = [Number(point.Latitude), Number(point.Longitude)];
            var displayLabel = label && label !== '—' ? label : 'Không rõ địa điểm';

            L.marker(latLng).addTo(map)
                .bindTooltip(displayLabel, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -18],
                    className: 'dat-gps-end-label dat-gps-end-label--' + typeClass
                })
                .bindPopup(
                    '<div class="dat-gps-popup">' +
                    '<strong>' + datEscapeHtml(displayLabel) + '</strong><br>' +
                    'Tọa độ: ' + datEscapeHtml(datCoordinateText(point)) + '<br>' +
                    'Tốc độ: ' + datEscapeHtml(point.VanToc != null ? point.VanToc : '-') + '<br>' +
                    'Thời gian: ' + datEscapeHtml(point.ThoiGianFont || '-') +
                    '</div>'
                );
        }

        function datRenderGpsMap($row, gpsData, meta) {
            if (!window.L || !Array.isArray(gpsData) || gpsData.length === 0) {
                datSetGpsRowError($row, 'Không có dữ liệu GPS');
                return;
            }

            var points = gpsData
                .filter(function (item) {
                    return item && item.Latitude != null && item.Longitude != null;
                })
                .map(function (item) {
                    return [Number(item.Latitude), Number(item.Longitude)];
                });

            if (points.length === 0) {
                datSetGpsRowError($row, 'Không có tọa độ hợp lệ');
                return;
            }

            datDestroyGpsMap($row);

            var $wrap = $row.find('.dat-gps-map-wrap');
            var $placeholder = $wrap.find('.dat-gps-map-placeholder');
            var $mapEl = $wrap.find('.dat-gps-map');
            var mapId = $mapEl.attr('id');

            $placeholder.hide();
            $mapEl.removeClass('d-none');

            var map = L.map(mapId, {
                scrollWheelZoom: false,
                attributionControl: true
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var line = L.polyline(points, {
                color: '#1976d2',
                weight: 5,
                opacity: 0.9,
                lineJoin: 'round',
                lineCap: 'round'
            }).addTo(map);

            var startPoint = gpsData[0];
            var endPoint = gpsData[gpsData.length - 1];
            meta = meta || {};

            var startLabel = datEndpointPlaceLabel(meta, 'start');
            var endLabel = datEndpointPlaceLabel(meta, 'end');

            datAddNamedEndpointMarker(map, startPoint, startLabel, 'start');
            if (gpsData.length > 1) {
                datAddNamedEndpointMarker(map, endPoint, endLabel, 'end');
            }

            $row.data('leafletMap', map);

            setTimeout(function () {
                map.invalidateSize();
                datFitMapBounds(map, line.getBounds());
            }, 100);
        }

        @if ($items !== null && $items->count() > 0)
        (function () {
            var doUrlTemplate = @json(route('daotao.pdt.dat.do-phien-tuyen-duong.tien-hanh-do', ['id' => 0]));
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var running = false;

            function doUrlForId(id) {
                return doUrlTemplate.replace(/\/0(\?|$)/, '/' + id + '$1');
            }

            $('#btnTienHanhDoTuyenDuong').on('click', function () {
                if (!hasXeOnlineBearer) {
                    $('#modalXeOnlineBearer').modal('show');
                    return;
                }

                if (running) {
                    return;
                }

                var $rows = $('#datDoTuyenDuongTable tbody tr[data-phien-id]');
                if ($rows.length === 0) {
                    return;
                }

                var $btn = $(this);
                running = true;
                $btn.prop('disabled', true).text('Đang dò...');

                $rows.each(function () {
                    datResetGpsRow($(this));
                });

                var chain = $.Deferred().resolve();

                $rows.each(function () {
                    var $row = $(this);
                    var phienId = $row.data('phien-id');

                    chain = chain.then(function () {
                        datSetGpsRowLoading($row);

                        return $.ajax({
                            url: doUrlForId(phienId),
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        }).done(function (data) {
                            if (data && data.ok) {
                                var duLieu = data.du_lieu || {};
                                var summary = datRouteSummaryText(data, duLieu);
                                $row.find('.col-tuyen-tom-tat').text(summary);
                                datRenderGpsMap($row, duLieu.list_coordinate || [], duLieu);
                                if (window.console) {
                                    console.log('GPS phiên #' + phienId, duLieu);
                                }
                            } else {
                                datSetGpsRowError($row, (data && data.message) ? data.message : 'Lỗi');
                            }
                        }).fail(function (xhr) {
                            var message = 'Lỗi kết nối';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            datSetGpsRowError($row, message);
                        });
                    });
                });

                chain.always(function () {
                    running = false;
                    $btn.prop('disabled', false).text('Tiến Hành Dò');
                });
            });
        })();
        @endif
    </script>
@endpush
