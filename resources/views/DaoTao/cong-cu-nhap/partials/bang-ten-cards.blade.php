<div class="bang-ten-spec">
    <div class="bang-ten-grid">
        @foreach ($preview['hoc_vien'] as $hv)
            @php
                $hoTen = trim((string) ($hv['ho_ten'] ?? '—'));
                $nameClass = mb_strlen($hoTen) > 22 ? 'bang-ten-name is-long' : 'bang-ten-name';
            @endphp
            <div class="bang-ten-card">
                <div class="bang-ten-header">
                    <div class="bang-ten-header-line">Sở Giáo dục &amp; Đào tạo Quảng Trị</div>
                    <div class="bang-ten-header-line">Trung tâm GDNN Mạnh Linh</div>
                </div>
                <div class="bang-ten-body">
                    <div class="bang-ten-photo-wrap">
                        @if (! empty($hv['anh_src']))
                            <img src="{{ $hv['anh_src'] }}" alt="{{ $hv['ho_ten'] }}"
                                 onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'bang-ten-photo-placeholder',textContent:'Không có ảnh'}))">
                        @else
                            <span class="bang-ten-photo-placeholder">Không có ảnh</span>
                        @endif
                    </div>
                    <div class="bang-ten-info">
                        <div class="bang-ten-title">Học viên tập lái xe</div>
                        <div class="{{ $nameClass }}">{{ $hoTen }}</div>
                        <div class="bang-ten-hang">Tập lái xe hạng: <strong>{{ $hv['hang_gplx'] ?? '—' }}</strong></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
