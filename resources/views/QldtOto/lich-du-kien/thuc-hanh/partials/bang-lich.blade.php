@php
    $nhanCa = \App\Models\QldtOto\BaiDay::CA;
    // Tên bài chứa từ khoá → màu ô; khớp nhiều từ thì lấy mục đứng trước.
    $mauTheoTuKhoa = ['cabin' => 'o-cabin', 'cao tốc' => 'o-cao-toc', 'ban đêm' => 'o-ban-dem', 'có tải' => 'o-co-tai'];
@endphp
<style>
    #lich-da-sinh .bang-lich { font-size: .82rem; }
    #lich-da-sinh .bang-lich th, #lich-da-sinh .bang-lich td { vertical-align: top; }
    /* Cột Ngày + Thứ đứng yên, các cặp cuộn ngang trong .khung-cuon. */
    #lich-da-sinh .khung-cuon { overflow-x: auto; }
    #lich-da-sinh .bang-lich { width: auto; min-width: 100%; border-collapse: separate; border-spacing: 0; border-width: 1px 0 0 1px; }
    #lich-da-sinh .bang-lich th,
    #lich-da-sinh .bang-lich td { border-width: 0 1px 1px 0; }
    #lich-da-sinh .bang-lich .chu-nghi { position: sticky; left: 9.75rem; display: inline-block; }
    #lich-da-sinh .bang-lich .col-ngay,
    #lich-da-sinh .bang-lich .col-thu { position: sticky; z-index: 2; background: #fff; }
    #lich-da-sinh .bang-lich .col-ngay { left: 0; white-space: nowrap; width: 6.5rem; min-width: 6.5rem; max-width: 6.5rem; }
    #lich-da-sinh .bang-lich .col-thu { left: 6.5rem; width: 2.75rem; min-width: 2.75rem; max-width: 2.75rem; text-align: center; box-shadow: inset -2px 0 0 #adb5bd; }
    #lich-da-sinh .bang-lich thead .col-ngay,
    #lich-da-sinh .bang-lich thead .col-thu { background: #e9ecef; z-index: 3; }
    #lich-da-sinh .bang-lich .thu-cn { color: #dc3545; font-weight: 600; }
    #lich-da-sinh .bang-lich .o-buoi { min-width: 12rem; }
    #lich-da-sinh .bang-lich .o-buoi.ca-chieu { background: #fbfbf3; }
    #lich-da-sinh .bang-lich .so-bai { font-weight: 600; color: #1f3a60; margin-right: .2rem; }
    #lich-da-sinh .bang-lich .gio { color: #6c757d; font-size: .75rem; }
    #lich-da-sinh .bang-lich .o-buoi.o-cabin { background: #e3f2fd; }
    #lich-da-sinh .bang-lich .o-buoi.o-cao-toc { background: #fff3bf; }
    #lich-da-sinh .bang-lich .o-buoi.o-ban-dem { background: #f1e8fb; }
    #lich-da-sinh .bang-lich .o-buoi.o-co-tai { background: #fde4e4; }
    #lich-da-sinh .bang-lich .o-buoi.o-tu-dong { background: #c4c9cf; }
    #lich-da-sinh .bang-lich .o-buoi.o-tu-dong .gio { color: #495057; }
    #lich-da-sinh .bang-lich tr.ngay-nghi td { background: #eef0f3; color: #6c757d; }
</style>

<div class="card card-panel" id="lich-da-sinh">
    <div class="card-header d-flex align-items-center">
        <span class="mr-auto">Lịch dự kiến</span>
        @if ($bangLich)
            <span class="small text-muted">
                Sinh lúc {{ $lich->NgaySinhLich?->format('d/m/Y H:i') }} · {{ $bangLich['so_buoi'] }} buổi · {{ count($bangLich['dong']) }} ngày
            </span>
        @endif
    </div>
    <div class="card-body">
        @foreach ((array) session('canh_bao_sinh_lich', []) as $canhBao)
            <div class="alert alert-warning py-2 mb-2">{{ $canhBao }}</div>
        @endforeach

        @if (! $bangLich)
            <p class="text-muted mb-0">
                Chưa sinh lịch. Bấm <strong>Sinh lịch</strong> để điền lần lượt các bài của
                Hạng {{ $hangOptions[$lich->Hang] ?? $lich->Hang }} (Sáng cho giáo viên sáng, Chiều cho giáo viên chiều)
                vào các ngày từ Ngày bắt đầu thực hành, bỏ qua ngày nghỉ.
            </p>
        @else
            <p class="small text-muted mb-2">
                Lịch chụp lại bài dạy lúc sinh. Sửa thông tin, ngày nghỉ, cặp giáo viên hoặc cấu hình bài dạy thì bấm <strong>Sinh lịch</strong> lại.
                Ô xanh nhạt là Cabin: cặp đầu sau "Cabin của cặp trước" ít nhất 1 ngày, mỗi cặp sau cách cặp trước 1 ngày. Màu: tím = Ban đêm, đỏ nhạt = Có tải, vàng = Cao tốc, xám đậm = Tự động.
            </p>
            <div class="khung-cuon">
                <table class="table table-sm table-bordered mb-0 bang-lich">
                    <thead class="thead-light">
                        <tr>
                            <th rowspan="2" class="col-ngay">Ngày</th>
                            <th rowspan="2" class="col-thu">Thứ</th>
                            @foreach (collect($bangLich['cot'])->groupBy('cap') as $cap => $cots)
                                <th colspan="{{ count($cots) }}" class="text-center">
                                    Cặp {{ $cap }}@if ($cots->first()['xe']) <span class="font-weight-normal text-muted">· xe {{ $cots->first()['xe'] }}</span>@endif
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($bangLich['cot'] as $c)
                                <th class="o-buoi {{ $c['ca'] === 'chieu' ? 'ca-chieu' : '' }}">
                                    {{ $nhanCa[$c['ca']] }}:
                                    <span class="font-weight-normal">{{ $c['ten_gv'] ?: '—' }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bangLich['dong'] as $d)
                            <tr class="{{ $d['nghi'] ? 'ngay-nghi' : '' }}">
                                <td class="col-ngay">{{ $d['ngay'] }}</td>
                                <td class="col-thu {{ $d['thu'] === 'CN' ? 'thu-cn' : '' }}">{{ $d['thu'] }}</td>
                                @if ($d['nghi'])
                                    <td colspan="{{ count($bangLich['cot']) }}"><span class="chu-nghi">Nghỉ{{ $d['ly_do_nghi'] !== '' ? ' — '.$d['ly_do_nghi'] : '' }}</span></td>
                                @else
                                    @foreach ($bangLich['cot'] as $c)
                                        @php
                                            $b = $d['o'][$c['key']] ?? null;
                                            $tenBai = $b ? mb_strtolower($b->NoiDung) : '';
                                            $mauO = str_starts_with($tenBai, 'tự động')
                                                ? 'o-tu-dong'
                                                : (collect($mauTheoTuKhoa)->first(fn ($cls, $tu) => $tenBai !== '' && str_contains($tenBai, $tu)) ?? '');
                                        @endphp
                                        <td class="o-buoi {{ $c['ca'] === 'chieu' ? 'ca-chieu' : '' }} {{ $mauO }}">
                                            @if ($b)
                                                <span class="so-bai" title="Bài thứ {{ $b->NgaySo }}">{{ $b->NgaySo }}.</span>{{ $b->NoiDung }}
                                                @if ($b->GioBatDau || $b->GioKetThuc || $b->SoGio)
                                                    <div class="gio">
                                                        {{ $b->GioBatDau ?: '—' }} → {{ $b->GioKetThuc ?: '—' }}@if ($b->SoGio) · {{ rtrim(rtrim(number_format($b->SoGio, 2, '.', ''), '0'), '.') }}h @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
