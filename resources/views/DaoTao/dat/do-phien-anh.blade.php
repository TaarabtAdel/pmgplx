@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Dò ảnh')

@push('styles')
<style>
    .dat-do-anh-table .cell-sub {
        display: block;
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.15rem;
    }
    .dat-do-anh-table .cell-sub.d-inline {
        display: inline;
        margin-top: 0;
        margin-left: 0.25rem;
    }
    .dat-do-anh-table .col-thong-tin,
    .dat-do-anh-table .col-thoi-gian {
        white-space: normal;
        vertical-align: top;
    }
    .dat-do-anh-table .col-anh {
        width: 55%;
        min-width: 22rem;
        vertical-align: top;
    }
    .dat-anh-wrap .dat-anh-placeholder.is-loading {
        background: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 4px;
    }
    .dat-anh-player {
        text-align: center;
    }
    .dat-anh-frame {
        background: #111;
        border-radius: 4px;
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .dat-anh-frame img {
        max-width: 100%;
        max-height: 280px;
        display: block;
    }
    .dat-anh-caption {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.4rem;
    }
    .dat-anh-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        margin-top: 0.5rem;
    }
    .dat-anh-controls .dat-anh-speed {
        margin-left: 0.5rem;
        font-size: 0.8rem;
        color: #495057;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .dat-anh-controls select {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.2rem 0.4rem;
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Dò ảnh</span>
            <div class="mt-1 mt-md-0">
                <button type="button" class="btn btn-sm btn-outline-secondary mr-1" data-toggle="modal" data-target="#modalDatAnhDuongDan">
                    Cấu hình Đường Dẫn Ảnh
                </button>
                <a href="{{ route('daotao.pdt.dat.do-phien-tuyen-duong') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Dò tuyến đường
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Chi tiết phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-canh-bao') }}" class="btn btn-sm btn-outline-secondary">
                    Điều kiện cảnh báo
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Chọn khóa học, cấu hình đường dẫn ảnh, rồi bấm <strong>Tải ảnh</strong> ở từng phiên.
                    Đường dẫn ảnh lưu tạm trong phiên làm việc (session).
                </p>
                <p class="mb-0">
                    @if ($hasAnhDuongDan)
                        Đường dẫn ảnh: <strong class="text-success">đã lưu</strong>
                        @if ($anhDuongDanPreview !== '')
                            (<code>{{ $anhDuongDanPreview }}</code>)
                        @endif
                    @else
                        Đường dẫn ảnh: <strong class="text-danger">chưa cấu hình</strong>
                        — bấm <strong>Cấu hình Đường Dẫn Ảnh</strong> trước khi tải.
                    @endif
                </p>
            </div>

            <form method="GET" action="{{ route('daotao.pdt.dat.do-phien-anh') }}" class="mb-3" id="datDoAnhFilterForm">
                @include('DaoTao.dat.partials.phien-filter', [
                    'resetRoute' => route('daotao.pdt.dat.do-phien-anh'),
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
                </div>

                @include('DaoTao.dat.partials.phien-ket-qua-bang-anh')
            @else
                <p class="text-muted mb-0">Chọn mã khóa học để tải ảnh phiên.</p>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalDatAnhDuongDan" tabindex="-1" role="dialog" aria-labelledby="modalDatAnhDuongDanLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('daotao.pdt.dat.do-phien-anh.luu-duong-dan') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDatAnhDuongDanLabel">Cấu hình Đường Dẫn Ảnh</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Đường dẫn lưu tạm trong <strong>phiên làm việc</strong> (session), không ghi DB.
                        Thư mục gốc chứa ảnh theo
                        <code>MA_KHOA_HOC/MA_HOC_VIEN/THANG/NGAY/HH-MM-SS.jpg</code>.
                    </p>
                    <div class="form-group mb-0">
                        <label for="input_duong_dan_anh" class="small font-weight-bold">Đường dẫn ảnh</label>
                        <input type="text" name="duong_dan_anh" id="input_duong_dan_anh"
                               class="form-control form-control-sm font-monospace @error('duong_dan_anh') is-invalid @enderror"
                               value="{{ old('duong_dan_anh', $anhDuongDan) }}"
                               placeholder="Ví dụ: /Users/tpt/Downloads/image-logs">
                        @error('duong_dan_anh')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($hasAnhDuongDan)
                        <p class="small text-muted mt-2 mb-0">
                            Đang lưu: <code>{{ $anhDuongDanPreview }}</code>
                        </p>
                    @endif
                </div>
                <div class="modal-footer">
                    @if ($hasAnhDuongDan)
                        <button type="submit" formaction="{{ route('daotao.pdt.dat.do-phien-anh.xoa-duong-dan') }}"
                                formmethod="POST" class="btn btn-sm btn-outline-danger mr-auto"
                                onclick="return confirm('Xóa đường dẫn ảnh khỏi phiên làm việc?');">
                            Xóa đường dẫn
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-navy">Lưu đường dẫn</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @include('DaoTao.dat.partials.phien-filter-scripts')

    <script>
        $('#filter_ma_khoa_hoc').on('change', function () {
            $('#datDoAnhFilterForm').submit();
        });

        @if ($errors->has('duong_dan_anh'))
            $('#modalDatAnhDuongDan').modal('show');
        @endif

        var hasAnhDuongDan = @json($hasAnhDuongDan);
        var taiAnhUrlTemplate = @json(route('daotao.pdt.dat.do-phien-anh.tai-anh', ['id' => 0]));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        function datTaiAnhUrl(id) {
            return taiAnhUrlTemplate.replace(/\/0(\?|$)/, '/' + id + '$1');
        }

        function datStopPlayer($row) {
            var timer = $row.data('anhTimer');
            if (timer) {
                clearInterval(timer);
                $row.removeData('anhTimer');
            }
            $row.find('.btn-anh-play').removeClass('d-none');
            $row.find('.btn-anh-stop').addClass('d-none');
        }

        function datShowSlide($row, index) {
            var slides = $row.data('anhSlides') || [];
            if (slides.length === 0) {
                return;
            }

            if (index < 0) {
                index = slides.length - 1;
            }
            if (index >= slides.length) {
                index = 0;
            }

            var slide = slides[index];
            $row.data('anhIndex', index);
            $row.find('.dat-anh-frame img').attr('src', slide.url).attr('alt', slide.ten);
            $row.find('.dat-anh-caption').text(
                (index + 1) + '/' + slides.length + ' · ' + slide.thoi_gian + ' · ' + slide.ten
            );
        }

        function datRenderPlayer($row, slides) {
            datStopPlayer($row);
            $row.data('anhSlides', slides);
            $row.data('anhIndex', 0);

            var $wrap = $row.find('.dat-anh-wrap');
            $wrap.find('.dat-anh-placeholder').hide();
            $wrap.find('.dat-anh-list').removeClass('d-none').html(
                '<div class="dat-anh-player">' +
                    '<div class="dat-anh-frame"><img alt=""></div>' +
                    '<div class="dat-anh-caption"></div>' +
                    '<div class="dat-anh-controls">' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary btn-anh-prev">Prev</button>' +
                        '<button type="button" class="btn btn-sm btn-navy btn-anh-play">Play</button>' +
                        '<button type="button" class="btn btn-sm btn-danger btn-anh-stop d-none">Stop</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-secondary btn-anh-next">Next</button>' +
                        '<label class="dat-anh-speed mb-0">' +
                            'Tốc độ' +
                            '<select class="form-control form-control-sm dat-anh-speed-select">' +
                                '<option value="500">0.5s</option>' +
                                '<option value="1000" selected>1s</option>' +
                                '<option value="2000">2s</option>' +
                                '<option value="3000">3s</option>' +
                            '</select>' +
                        '</label>' +
                    '</div>' +
                '</div>'
            );
            $row.find('.col-anh-tom-tat').text(slides.length + ' ảnh');
            datShowSlide($row, 0);
        }

        function datSetAnhError($row, message) {
            datStopPlayer($row);
            $row.removeData('anhSlides');
            $row.find('.dat-anh-list').addClass('d-none').empty();
            $row.find('.dat-anh-placeholder').show().removeClass('is-loading');
            $row.find('.btn-tai-anh').prop('disabled', false).text('Tải ảnh');
            $row.find('.col-anh-tom-tat').addClass('text-danger').text(message);
        }

        $(document).on('click', '.btn-tai-anh', function () {
            if (!hasAnhDuongDan) {
                $('#modalDatAnhDuongDan').modal('show');
                return;
            }

            var $row = $(this).closest('tr');
            var phienId = $row.data('phien-id');
            var $btn = $(this);

            $row.find('.col-anh-tom-tat').removeClass('text-danger').text('');
            $row.find('.dat-anh-placeholder').addClass('is-loading');
            $btn.prop('disabled', true).text('Đang tải...');

            $.ajax({
                url: datTaiAnhUrl(phienId),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            }).done(function (data) {
                if (data && data.ok && Array.isArray(data.anh) && data.anh.length > 0) {
                    datRenderPlayer($row, data.anh);
                    return;
                }

                if (data && data.ok) {
                    datSetAnhError($row, 'Không có ảnh trong khoảng thời gian phiên.');
                    return;
                }

                datSetAnhError($row, (data && data.message) ? data.message : 'Không tải được ảnh.');
            }).fail(function (xhr) {
                var message = 'Lỗi kết nối';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                datSetAnhError($row, message);
            });
        });

        $(document).on('click', '.btn-anh-prev', function () {
            var $row = $(this).closest('tr');
            datShowSlide($row, ($row.data('anhIndex') || 0) - 1);
        });

        $(document).on('click', '.btn-anh-next', function () {
            var $row = $(this).closest('tr');
            datShowSlide($row, ($row.data('anhIndex') || 0) + 1);
        });

        $(document).on('click', '.btn-anh-play', function () {
            var $row = $(this).closest('tr');
            var slides = $row.data('anhSlides') || [];
            if (slides.length < 2) {
                return;
            }

            datStopPlayer($row);
            $row.find('.btn-anh-play').addClass('d-none');
            $row.find('.btn-anh-stop').removeClass('d-none');

            var speed = parseInt($row.find('.dat-anh-speed-select').val(), 10) || 1000;
            var timer = setInterval(function () {
                var index = $row.data('anhIndex') || 0;
                if (index >= slides.length - 1) {
                    datStopPlayer($row);
                    return;
                }
                datShowSlide($row, index + 1);
            }, speed);

            $row.data('anhTimer', timer);
        });

        $(document).on('click', '.btn-anh-stop', function () {
            datStopPlayer($(this).closest('tr'));
        });

        $(document).on('change', '.dat-anh-speed-select', function () {
            var $row = $(this).closest('tr');
            if ($row.data('anhTimer')) {
                $row.find('.btn-anh-play').trigger('click');
            }
        });
    </script>
@endpush
