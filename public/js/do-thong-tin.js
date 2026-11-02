/**
 * Dò thông tin CCCD / GPLX từ ảnh — dùng chung cho modal «Dò thông tin» (máy tính) và trang chụp trên điện thoại.
 * Ảnh được thu nhỏ rồi gửi lên server (Google Vision ở server, trình duyệt không thấy API key).
 * Quét camera liên tục cần HTTPS; trên HTTP vẫn chụp ảnh được qua <input capture>.
 */
(function (global) {
    'use strict';

    var MAX_SIDE = 2400;

    var MAT = {
        cccd_truoc: { nhan: 'CCCD mặt trước', can: ['so_cccd', 'ho_ten', 'ngay_sinh'] },
        cccd_sau: { nhan: 'CCCD mặt sau', can: ['ngay_cap_cccd'] },
        gplx_truoc: { nhan: 'GPLX mặt trước', can: ['so_gplx', 'ngay_cap_gplx'] },
        gplx_sau: { nhan: 'GPLX mặt sau', can: ['ngay_trung_tuyen'] },
    };

    var TRUONG = {
        ho_ten: 'Tên',
        so_cccd: 'Số căn cước',
        ngay_sinh: 'Ngày sinh',
        noi_thuong_tru: 'Nơi thường trú',
        ngay_cap_cccd: 'Ngày cấp CCCD',
        so_gplx: 'Số giấy phép',
        ngay_cap_gplx: 'Ngày cấp GPLX',
        ngay_trung_tuyen: 'Ngày trúng tuyển',
    };

    var coTheQuet = !!(global.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    /** Điện thoại / máy tính bảng (màn cảm ứng): không gợi ý kéo thả, dán ảnh. */
    var coCamCam = !!(global.matchMedia && global.matchMedia('(pointer: coarse)').matches);

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /** Thu nhỏ ảnh quá lớn trước khi gửi — Vision vẫn đọc tốt ở ~2400px. */
    function docAnh(file) {
        return new Promise(function (resolve, reject) {
            if (!file || !/^image\//.test(file.type)) return reject(new Error('Vui lòng chọn file ảnh'));
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                var scale = Math.min(1, MAX_SIDE / Math.max(img.width, img.height));
                var c = document.createElement('canvas');
                c.width = Math.round(img.width * scale);
                c.height = Math.round(img.height * scale);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                URL.revokeObjectURL(url);
                resolve(c.toDataURL('image/jpeg', 0.9));
            };
            img.onerror = function () { reject(new Error('Không đọc được ảnh này')); };
            img.src = url;
        });
    }

    function goiOcr(cfg, mat, dataUrl) {
        return fetch(cfg.urlOcr, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
            body: JSON.stringify({ mat: mat, image: dataUrl }),
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || ('Lỗi ' + res.status));
                return data;
            });
        });
    }

    /** Cùng quy tắc với DoThongTinGiayTo::chuanSoSanh (server lưu Khớp / Không khớp theo quy tắc này). */
    function chuanSoSanh(key, v) {
        v = String(v == null ? '' : v).normalize('NFC').trim();
        if (key === 'so_cccd' || key === 'so_gplx') return v.replace(/\D/g, '');
        if (key.indexOf('ngay_') === 0) {
            var m = v.match(/^(\d{1,2})\D(\d{1,2})\D(\d{4})$/);
            return m ? m[3] + ('0' + m[2]).slice(-2) + ('0' + m[1]).slice(-2) : v;
        }
        return v.toLowerCase().replace(/[.,;]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function khop(key, giaTriDo, giaTriPm) {
        var a = chuanSoSanh(key, giaTriDo);
        return a !== '' && a === chuanSoSanh(key, giaTriPm);
    }

    function duThongTin(mat, ketQua) {
        return MAT[mat].can.every(function (k) { return ketQua && ketQua[k]; });
    }

    /* ---------- Quét camera liên tục (cần HTTPS) ---------- */

    var quet = null;

    function overlayQuet() {
        var el = document.getElementById('dtt-scanner');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'dtt-scanner';
        el.innerHTML =
            '<video playsinline muted autoplay></video>' +
            '<div class="dtt-frame"></div>' +
            '<div class="dtt-bar"><div class="dtt-hint"></div>' +
            '<div><button type="button" class="btn btn-light btn-sm dtt-torch" hidden>Đèn</button> ' +
            '<button type="button" class="btn btn-light btn-sm dtt-close">Đóng</button></div></div>';
        document.body.appendChild(el);
        el.querySelector('.dtt-close').addEventListener('click', function () { dungQuet(); });
        el.querySelector('.dtt-torch').addEventListener('click', function () {
            if (!quet || !quet.stream) return;
            quet.den = !quet.den;
            quet.stream.getVideoTracks()[0].applyConstraints({ advanced: [{ torch: quet.den }] }).catch(function () {});
        });
        return el;
    }

    function doNet(video, ctx) {
        var W = 160;
        var h = Math.round((W * video.videoHeight) / video.videoWidth) || 120;
        ctx.canvas.width = W;
        ctx.canvas.height = h;
        ctx.drawImage(video, 0, 0, W, h);
        var px = ctx.getImageData(0, 0, W, h).data;
        var gray = new Float32Array(W * h);
        for (var i = 0; i < gray.length; i++) gray[i] = px[i * 4] * 0.299 + px[i * 4 + 1] * 0.587 + px[i * 4 + 2] * 0.114;
        var sharp = 0, motion = 0;
        for (var y = 1; y < h; y++) {
            for (var x = 1; x < W; x++) {
                var j = y * W + x;
                sharp += Math.abs(gray[j] - gray[j - 1]) + Math.abs(gray[j] - gray[j - W]);
            }
        }
        if (quet.prev) for (var k = 0; k < gray.length; k++) motion += Math.abs(gray[k] - quet.prev[k]);
        var coTruoc = !!quet.prev;
        quet.prev = gray;
        return { sharp: sharp / gray.length, motion: coTruoc ? motion / gray.length : 99 };
    }

    function chupKhung(video) {
        var scale = Math.min(1, MAX_SIDE / Math.max(video.videoWidth, video.videoHeight));
        var c = document.createElement('canvas');
        c.width = Math.round(video.videoWidth * scale);
        c.height = Math.round(video.videoHeight * scale);
        c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
        return c.toDataURL('image/jpeg', 0.9);
    }

    function nhipQuet() {
        var el = overlayQuet();
        var video = el.querySelector('video');
        var hint = el.querySelector('.dtt-hint');
        var frame = el.querySelector('.dtt-frame');
        if (!quet || !quet.stream || video.readyState < 2) return;
        var m = doNet(video, quet.ctx);
        var yen = m.motion < 4 && m.sharp > 6;
        quet.yen = yen ? quet.yen + 1 : 0;
        frame.classList.toggle('ready', quet.yen >= 2);
        if (quet.dangGui || quet.yen < 2 || Date.now() - quet.lanGui < 1500) {
            if (!quet.dangGui) hint.textContent = yen ? 'Giữ yên…' : 'Đưa ' + MAT[quet.mat].nhan + ' vào khung, giữ yên tay';
            return;
        }
        quet.dangGui = true;
        quet.lanGui = Date.now();
        quet.soLan++;
        frame.classList.add('busy');
        hint.textContent = 'Đang nhận dạng…';
        var anh = chupKhung(video);
        goiOcr(quet.cfg, quet.mat, anh).then(function (kq) {
            quet.gop = quet.gop || { ket_qua: {} };
            Object.keys(kq.ket_qua || {}).forEach(function (k) {
                if (!quet.gop.ket_qua[k]) quet.gop.ket_qua[k] = kq.ket_qua[k];
            });
            quet.gop = Object.assign({}, kq, { ket_qua: quet.gop.ket_qua });
            quet.anh = anh;
            var du = duThongTin(quet.mat, quet.gop.ket_qua);
            if (du || quet.soLan >= 10) {
                if (navigator.vibrate) navigator.vibrate(120);
                var xong = quet.xong, gop = quet.gop, a = quet.anh;
                dungQuet();
                xong(null, gop, a);
                return;
            }
            var n = Object.keys(quet.gop.ket_qua).length;
            hint.textContent = n ? 'Đã đọc ' + n + ' thông tin, giữ yên để đọc thêm…' : 'Chưa thấy chữ, đưa giấy tờ gần hơn';
        }).catch(function (err) {
            hint.textContent = err.message;
        }).then(function () {
            if (quet) quet.dangGui = false;
            frame.classList.remove('busy');
        });
    }

    function batDauQuet(cfg, mat, xong) {
        return navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            audio: false,
        }).then(function (stream) {
            var el = overlayQuet();
            quet = {
                cfg: cfg, mat: mat, xong: xong, stream: stream, prev: null, yen: 0, dangGui: false, lanGui: 0, soLan: 0,
                gop: null, den: false, ctx: document.createElement('canvas').getContext('2d', { willReadFrequently: true }),
            };
            el.querySelector('video').srcObject = stream;
            el.classList.add('show');
            var track = stream.getVideoTracks()[0];
            el.querySelector('.dtt-torch').hidden = !(track.getCapabilities && track.getCapabilities().torch);
            quet.timer = setInterval(nhipQuet, 250);
        });
    }

    function dungQuet() {
        if (!quet) return;
        clearInterval(quet.timer);
        quet.stream.getTracks().forEach(function (t) { t.stop(); });
        var el = overlayQuet();
        el.querySelector('video').srcObject = null;
        el.classList.remove('show');
        quet = null;
    }

    document.addEventListener('visibilitychange', function () { if (document.hidden) dungQuet(); });

    /* ---------- Ô chụp từng mặt giấy tờ ---------- */

    /**
     * Vẽ 4 ô (CCCD trước/sau, GPLX trước/sau) vào container.
     * cfg: { urlOcr, csrf, onKetQua(mat, ketQua) } — trang điện thoại dùng urlOcr có sẵn mã phiên.
     */
    function ganO(container, cfg) {
        container.innerHTML = Object.keys(MAT).map(function (mat) {
            return '<div class="dtt-o" data-mat="' + mat + '">' +
                '<div class="dtt-o-head"><strong>' + MAT[mat].nhan + '</strong><span class="dtt-trang-thai"></span></div>' +
                '<div class="dtt-anh"><span>' + (coCamCam ? 'Chưa có ảnh' : 'Kéo thả / dán (Ctrl+V) ảnh vào đây') + '</span><img alt="" hidden></div>' +
                // HTTP không quét liên tục được: «Quét camera» mở thẳng camera điện thoại để chụp một ảnh.
                '<div class="dtt-nut">' +
                '<button type="button" class="btn btn-sm btn-primary dtt-quet">Quét camera</button>' +
                '<input type="file" accept="image/*" capture="environment" hidden class="dtt-chup">' +
                '<label class="btn btn-sm btn-outline-secondary mb-0">Chọn ảnh<input type="file" accept="image/*" hidden class="dtt-chon"></label>' +
                '</div>' +
                '<dl class="dtt-ket-qua"></dl>' +
                '</div>';
        }).join('');

        function o(mat) { return container.querySelector('.dtt-o[data-mat="' + mat + '"]'); }

        function trangThai(mat, text, loai) {
            var s = o(mat).querySelector('.dtt-trang-thai');
            s.textContent = text || '';
            s.className = 'dtt-trang-thai' + (loai ? ' ' + loai : '');
        }

        function hienAnh(mat, dataUrl) {
            var img = o(mat).querySelector('.dtt-anh img');
            img.src = dataUrl;
            img.hidden = false;
            o(mat).querySelector('.dtt-anh span').hidden = true;
        }

        function hienKetQua(mat, kq) {
            var dl = o(mat).querySelector('.dtt-ket-qua');
            var html = Object.keys(kq.ket_qua || {}).map(function (k) {
                return '<dt>' + esc(TRUONG[k] || k) + '</dt><dd>' + esc(kq.ket_qua[k]) + '</dd>';
            }).join('');
            if (kq.canh_bao) html += '<dd class="dtt-canh-bao">' + esc(kq.canh_bao) + '</dd>';
            dl.innerHTML = html;
            var du = duThongTin(mat, kq.ket_qua);
            trangThai(mat, du ? '✓ Đã đọc' : (kq.canh_bao ? 'Thiếu thông tin' : 'Một phần'), du ? 'ok' : 'warn');
            if (cfg.onKetQua) cfg.onKetQua(mat, kq);
        }

        function guiAnh(mat, dataUrl) {
            hienAnh(mat, dataUrl);
            trangThai(mat, 'Đang nhận dạng…', 'busy');
            return goiOcr(cfg, mat, dataUrl).then(function (kq) { hienKetQua(mat, kq); })
                .catch(function (err) { trangThai(mat, err.message, 'err'); });
        }

        container.addEventListener('change', function (e) {
            var input = e.target;
            if (!input.classList.contains('dtt-chup') && !input.classList.contains('dtt-chon')) return;
            var mat = input.closest('.dtt-o').dataset.mat;
            var file = input.files && input.files[0];
            input.value = '';
            if (!file) return;
            docAnh(file).then(function (d) { return guiAnh(mat, d); })
                .catch(function (err) { trangThai(mat, err.message, 'err'); });
        });

        // Kéo thả ảnh vào ô nào thì dò theo mặt giấy tờ của ô đó.
        function oDangKeo(e) {
            var ob = e.target.closest && e.target.closest('.dtt-o');
            return ob && container.contains(ob) ? ob : null;
        }
        ['dragenter', 'dragover'].forEach(function (ev) {
            container.addEventListener(ev, function (e) {
                var ob = oDangKeo(e);
                if (!ob) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                ob.classList.add('dtt-keo');
            });
        });
        container.addEventListener('dragleave', function (e) {
            var ob = oDangKeo(e);
            if (ob && !ob.contains(e.relatedTarget)) ob.classList.remove('dtt-keo');
        });
        function nhanFile(mat, files) {
            var file = Array.prototype.find.call(files || [], function (f) { return /^image\//.test(f.type); });
            if (!file) {
                trangThai(mat, 'Chỉ nhận file ảnh', 'err');
                return;
            }
            docAnh(file).then(function (d) { return guiAnh(mat, d); })
                .catch(function (err) { trangThai(mat, err.message, 'err'); });
        }

        container.addEventListener('drop', function (e) {
            var ob = oDangKeo(e);
            if (!ob) return;
            e.preventDefault();
            ob.classList.remove('dtt-keo');
            nhanFile(ob.dataset.mat, e.dataTransfer.files);
        });

        function dangHien() { return container.offsetParent !== null; }

        // Thả trượt ra ngoài ô khi modal đang mở: chặn trình duyệt mở ảnh đè lên trang.
        ['dragover', 'drop'].forEach(function (ev) {
            document.addEventListener(ev, function (e) {
                if (dangHien() && !oDangKeo(e)) e.preventDefault();
            });
        });

        // Dán ảnh (Ctrl+V — ảnh chụp màn hình, copy từ Zalo…): vào ô đang rê chuột, không thì ô đầu tiên còn trống.
        var oDangRe = null;
        container.addEventListener('mouseover', function (e) { oDangRe = oDangKeo(e) || oDangRe; });
        container.addEventListener('mouseleave', function () { oDangRe = null; });
        document.addEventListener('paste', function (e) {
            if (!dangHien() || /^(INPUT|TEXTAREA)$/.test((e.target && e.target.tagName) || '')) return;
            var files = Array.prototype.map.call(e.clipboardData ? e.clipboardData.items : [], function (it) {
                return it.kind === 'file' ? it.getAsFile() : null;
            }).filter(Boolean);
            if (!files.length) return;
            var ob = oDangRe || Array.prototype.find.call(container.querySelectorAll('.dtt-o'), function (x) {
                return x.querySelector('.dtt-anh img').hidden;
            }) || container.querySelector('.dtt-o');
            e.preventDefault();
            nhanFile(ob.dataset.mat, files);
        });

        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.dtt-quet');
            if (!btn) return;
            var ob = btn.closest('.dtt-o');
            var mat = ob.dataset.mat;
            trangThai(mat, '', '');
            if (!coTheQuet) {
                ob.querySelector('.dtt-chup').click();
                return;
            }
            batDauQuet(cfg, mat, function (err, kq, anh) {
                if (anh) hienAnh(mat, anh);
                hienKetQua(mat, kq);
            }).catch(function (err) {
                trangThai(mat, 'Không mở được camera: ' + (err.name === 'NotAllowedError' ? 'chưa cho phép truy cập camera' : err.message), 'err');
                ob.querySelector('.dtt-chup').click();
            });
        });

        function datLai() {
            Object.keys(MAT).forEach(function (mat) {
                var img = o(mat).querySelector('.dtt-anh img');
                img.hidden = true;
                img.removeAttribute('src');
                o(mat).querySelector('.dtt-anh span').hidden = false;
                o(mat).querySelector('.dtt-ket-qua').innerHTML = '';
                trangThai(mat, '', '');
            });
        }

        return { hienKetQua: hienKetQua, trangThai: trangThai, hienAnh: hienAnh, datLai: datLai };
    }

    global.DoThongTin = { MAT: MAT, TRUONG: TRUONG, coTheQuet: coTheQuet, ganO: ganO, esc: esc, khop: khop };
})(window);
