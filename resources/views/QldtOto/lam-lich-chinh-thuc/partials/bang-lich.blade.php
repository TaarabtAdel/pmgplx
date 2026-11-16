@php
    $nhanCa = \App\Models\QldtOto\BaiDay::CA;
    $mauTheoTuKhoa = ['cabin' => 'o-cabin', 'ban đêm' => 'o-ban-dem', 'cao tốc' => 'o-cao-toc', 'có tải' => 'o-co-tai'];
    $coKeoThuTu = $coKeoThuTu ?? true;
@endphp
<style>
    #lich-chinh-thuc .bang-lich { font-size: .82rem; }
    #lich-chinh-thuc .bang-lich th, #lich-chinh-thuc .bang-lich td { vertical-align: top; }
    #lich-chinh-thuc .khung-cuon { overflow-x: auto; }
    #lich-chinh-thuc .bang-lich { width: auto; min-width: 100%; border-collapse: separate; border-spacing: 0; border-width: 1px 0 0 1px; }
    #lich-chinh-thuc .bang-lich th,
    #lich-chinh-thuc .bang-lich td { border-width: 0 1px 1px 0; }
    #lich-chinh-thuc .bang-lich .chu-nghi { position: sticky; left: 9.75rem; display: inline-block; }
    #lich-chinh-thuc .bang-lich .col-ngay,
    #lich-chinh-thuc .bang-lich .col-thu { position: sticky; z-index: 2; background: #fff; }
    #lich-chinh-thuc .bang-lich .col-ngay { left: 0; white-space: nowrap; width: 6.5rem; min-width: 6.5rem; max-width: 6.5rem; }
    #lich-chinh-thuc .bang-lich .col-thu { left: 6.5rem; width: 2.75rem; min-width: 2.75rem; max-width: 2.75rem; text-align: center; box-shadow: inset -2px 0 0 #adb5bd; }
    #lich-chinh-thuc .bang-lich thead .col-ngay,
    #lich-chinh-thuc .bang-lich thead .col-thu { background: #e9ecef; z-index: 3; }
    #lich-chinh-thuc .bang-lich .thu-cn { color: #dc3545; font-weight: 600; }
    #lich-chinh-thuc .bang-lich .o-buoi { min-width: 12rem; }
    #lich-chinh-thuc .bang-lich .o-buoi.ca-chieu { background: #fbfbf3; }
    #lich-chinh-thuc .bang-lich .so-bai { font-weight: 600; color: #1f3a60; margin-right: .2rem; }
    #lich-chinh-thuc .bang-lich .gio { color: #6c757d; font-size: .75rem; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-cabin { background: #e3f2fd; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-cao-toc { background: #ffe566; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-ban-dem { background: #c77dff; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-co-tai { background: #fde4e4; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-tu-dong { background: #c4c9cf; }
    #lich-chinh-thuc .bang-lich .o-buoi.o-tu-dong .gio { color: #495057; }
    #lich-chinh-thuc .bang-lich tr.ngay-nghi td { background: #eef0f3; color: #6c757d; }
    #lich-chinh-thuc .o-buoi-slot { list-style: none; margin: 0; padding: 0; min-height: 1.75rem; }
    #lich-chinh-thuc .o-buoi-keo {
        cursor: grab; border-radius: .2rem; padding: .15rem .2rem;
        border: 1px dashed transparent;
    }
    #lich-chinh-thuc .o-buoi-keo:hover { border-color: #adb5bd; background: rgba(255,255,255,.5); }
    #lich-chinh-thuc .o-buoi-keo.dang-keo { cursor: grabbing; opacity: .85; box-shadow: 0 2px 6px rgba(0,0,0,.12); background: #fff; }
    #lich-chinh-thuc .o-buoi-slot .sortable-ghost { opacity: .35; }
    #lich-chinh-thuc .huong-dan-keo { font-size: .8rem; }
</style>

<div class="card card-panel" id="lich-chinh-thuc">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span class="mr-auto">Xem lại lịch chính thức</span>
        @if ($bangLich)
            <span class="small text-muted">{{ $bangLich['so_buoi'] }} buổi · {{ count($bangLich['dong']) }} ngày</span>
            @if ($coKeoThuTu)
                <button type="submit" form="form-thu-tu-tren-bang" class="btn btn-sm btn-primary">Lưu thứ tự bài</button>
            @endif
            <a href="{{ route('qldtoto.lam-lich-chinh-thuc.export-excel', $lich) }}" class="btn btn-sm btn-outline-success">
                Xuất Excel (mẫu nhập lịch)
            </a>
        @endif
    </div>
    <div class="card-body">
        @if (! $bangLich)
            <p class="text-muted mb-0">Chưa có buổi trong lịch chính thức.</p>
        @else
            @if ($coKeoThuTu)
                <p class="text-muted huong-dan-keo mb-2">
                    Kéo bài trong <strong>cùng cột</strong> (cùng GV · ca) sang ô ngày khác: chỉ đổi nội dung bài, ngày trên lịch giữ nguyên.
                    Bấm <strong>Lưu thứ tự bài</strong> khi xong.
                </p>
                <form method="POST" action="{{ route('qldtoto.lam-lich-chinh-thuc.update-thu-tu', $lich) }}" id="form-thu-tu-tren-bang">
                    @csrf
                    @method('PUT')
                    <div id="thu-tu-bai-an"></div>
            @endif
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
                                    {{ $nhanCa[$c['ca']] ?? $c['ca'] }}:
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
                                            <ul class="o-buoi-slot" data-cot="{{ $c['key'] }}">
                                                @if ($b)
                                                    <li class="o-buoi-keo" data-id="{{ $b->Id }}">
                                                        <span class="so-bai" title="Bài thứ {{ $b->NgaySo }}">{{ $b->NgaySo }}.</span>{{ $b->NoiDung }}
                                                        @if ($b->GioBatDau || $b->GioKetThuc || $b->SoGio)
                                                            <div class="gio">
                                                                {{ $b->GioBatDau ?: '—' }} → {{ $b->GioKetThuc ?: '—' }}@if ($b->SoGio) · {{ rtrim(rtrim(number_format($b->SoGio, 2, '.', ''), '0'), '.') }}h @endif
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endif
                                            </ul>
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($coKeoThuTu)
                </form>
            @endif
        @endif
    </div>
</div>