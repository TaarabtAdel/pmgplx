(function ($) {
    'use strict';

    var cfg = window.lichThucHanhForm || {};
    var giaoViens = cfg.giaoViens || [];
    var bienSoXe = cfg.bienSoXe || [];

    function gvOptionsHtml(selected) {
        var html = '<option value="">— Chọn GV —</option>';
        giaoViens.forEach(function (gv) {
            var sel = gv.ma === selected ? ' selected' : '';
            html += '<option value="' + escapeAttr(gv.ma) + '" data-ten="' + escapeAttr(gv.ten) + '"' + sel + '>' +
                escapeHtml(gvSelectLabel(gv)) + '</option>';
        });
        return html;
    }

    function escapeHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function escapeAttr(s) {
        return escapeHtml(s);
    }

    function reindexCapXe() {
        $('#cap-xe-body tr').each(function (idx) {
            var $tr = $(this);
            $tr.find('[name^="cap_xe["]').each(function () {
                var name = $(this).attr('name');
                if (!name) {
                    return;
                }
                var field = name.replace(/^cap_xe\[\d+\]/, '').replace(/^\[/, '').replace(/\]$/, '');
                $(this).attr('name', 'cap_xe[' + idx + '][' + field + ']');
            });
            $tr.find('.cap-stt-label').text(idx + 1);
            $tr.find('input.cap-stt-input').val(idx + 1);
        });
    }

    function gvSelectLabel(gv) {
        var ma = String(gv.ma || '').trim();
        var ten = String(gv.ten || '').trim();
        if (ma === '') {
            return ten || '—';
        }
        return ten !== '' ? ten + ' (' + ma + ')' : ma;
    }

    function selectedGvTen($sel) {
        var $opt = $sel.find('option:selected');
        var ten = $opt.attr('data-ten');
        if (ten) {
            return ten;
        }
        var text = ($opt.text() || '').trim();
        var m = text.match(/^(.+)\s+\([^)]+\)\s*$/);
        return m ? m[1].trim() : text;
    }

    function bindCapRow($tr) {
        $tr.find('.gv-select').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '— Chọn GV —',
            allowClear: true
        }).on('change', function () {
            var $sel = $(this);
            var ten = selectedGvTen($sel);
            var target = $sel.data('ten-target');
            if (target) {
                $tr.find(target).val(ten);
            }
        });
    }

    function addCapRow(data) {
        data = data || {};
        var idx = $('#cap-xe-body tr').length;
        var $tr = $('<tr></tr>');
        $tr.append(
            '<td class="text-center align-middle"><span class="cap-stt-label">' + (data.stt || idx + 1) + '</span>' +
            '<input type="hidden" class="cap-stt-input" name="cap_xe[' + idx + '][stt]" value="' + (data.stt || idx + 1) + '"></td>'
        );
        $tr.append(
            '<td><input type="text" class="form-control form-control-sm" name="cap_xe[' + idx + '][bien_so]" ' +
            'list="datalist-bien-so" value="' + escapeAttr(data.bien_so || '') + '" placeholder="74A-…"></td>'
        );
        $tr.append(
            '<td><select class="form-control form-control-sm gv-select" name="cap_xe[' + idx + '][gv_sang]" ' +
            'data-ten-target=".ten-sang">' + gvOptionsHtml(data.gv_sang || '') + '</select>' +
            '<input type="hidden" class="ten-sang" name="cap_xe[' + idx + '][ten_gv_sang]" value="' + escapeAttr(data.ten_gv_sang || '') + '"></td>'
        );
        $tr.append(
            '<td><select class="form-control form-control-sm gv-select" name="cap_xe[' + idx + '][gv_chieu]" ' +
            'data-ten-target=".ten-chieu">' + gvOptionsHtml(data.gv_chieu || '') + '</select>' +
            '<input type="hidden" class="ten-chieu" name="cap_xe[' + idx + '][ten_gv_chieu]" value="' + escapeAttr(data.ten_gv_chieu || '') + '"></td>'
        );
        $tr.append(
            '<td><input type="number" min="1" max="99" class="form-control form-control-sm" name="cap_xe[' + idx + '][so_hoc_vien_gv_sang]" value="' +
            (data.so_hoc_vien_gv_sang != null ? data.so_hoc_vien_gv_sang : (cfg.soHocVienMacDinh || 5)) + '"></td>'
        );
        $tr.append(
            '<td><input type="number" min="1" max="99" class="form-control form-control-sm" name="cap_xe[' + idx + '][so_hoc_vien_gv_chieu]" value="' +
            (data.so_hoc_vien_gv_chieu != null ? data.so_hoc_vien_gv_chieu : (cfg.soHocVienMacDinh || 5)) + '"></td>'
        );
        $tr.append(
            '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-cap" title="Xóa dòng">&times;</button></td>'
        );
        $('#cap-xe-body').append($tr);
        bindCapRow($tr);
        $tr.find('.gv-select').trigger('change');
    }

    function parseLocalDate(iso) {
        var p = String(iso || '').split('-');
        if (p.length !== 3) {
            return null;
        }
        return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    }

    function formatLocalDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function addNghiNgayRow(listId, value) {
        var fieldName = 'nghi_co_dinh';
        var $row = $('<div class="input-group input-group-sm mb-1 nghi-ngay-row"></div>');
        $row.append('<input type="date" class="form-control" name="' + fieldName + '[]" value="' + escapeAttr(value || '') + '">');
        $row.append('<div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-xoa-ngay">&times;</button></div>');
        $('#' + listId).append($row);
    }

    function fillNghiCoDinhThuNam() {
        var startIso = $('[name="ngay_khai_giang"]').val();
        var endIso = $('[name="ngay_ket_thuc_du_kien"]').val();
        if (!startIso) {
            window.alert('Chọn ngày khai giảng trước.');
            return;
        }
        var start = parseLocalDate(startIso);
        var end = endIso ? parseLocalDate(endIso) : null;
        if (!start) {
            return;
        }
        if (!end) {
            end = new Date(start.getTime());
            end.setDate(end.getDate() + 47);
        }
        if (end < start) {
            window.alert('Ngày kết thúc phải từ ngày khai giảng trở đi (hoặc để trống).');
            return;
        }
        var existing = {};
        $('#nghi-co-dinh-list input[type="date"]').each(function () {
            var v = $(this).val();
            if (v) {
                existing[v] = true;
            }
        });
        var hasRows = $('#nghi-co-dinh-list .nghi-ngay-row').length > 0;
        if (hasRows && !window.confirm('Thêm các ngày thứ năm còn thiếu vào danh sách? (Không xóa ngày đã có)')) {
            return;
        }
        var cur = new Date(start.getTime());
        var added = 0;
        while (cur <= end) {
            if (cur.getDay() === 4) {
                var iso = formatLocalDate(cur);
                if (!existing[iso]) {
                    addNghiNgayRow('nghi-co-dinh-list', iso);
                    existing[iso] = true;
                    added++;
                }
            }
            cur.setDate(cur.getDate() + 1);
        }
        if (added === 0) {
            window.alert('Không có ngày thứ năm mới để thêm trong khoảng đã chọn.');
        }
    }

    function addNghiBuRow(tu, den) {
        var idx = $('#nghi-bu-list .nghi-bu-row').length;
        var $row = $(
            '<tr class="nghi-bu-row">' +
            '<td><input type="date" class="form-control form-control-sm" name="nghi_bu[' + idx + '][tu]" value="' + escapeAttr(tu || '') + '"></td>' +
            '<td><input type="date" class="form-control form-control-sm" name="nghi_bu[' + idx + '][den]" value="' + escapeAttr(den || '') + '"></td>' +
            '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-bu">&times;</button></td>' +
            '</tr>'
        );
        $('#nghi-bu-list').append($row);
    }

    function reindexNghiBu() {
        $('#nghi-bu-list .nghi-bu-row').each(function (idx) {
            $(this).find('[name^="nghi_bu["]').each(function () {
                var field = $(this).attr('name').split('][').pop().replace(']', '');
                $(this).attr('name', 'nghi_bu[' + idx + '][' + field + ']');
            });
        });
    }

    function addNghiRiengNgayRow($list, rowIdx, value) {
        $list.append(
            '<div class="input-group input-group-sm mb-1">' +
            '<input type="date" class="form-control" name="nghi_rieng[' + rowIdx + '][ngay][]" value="' + escapeAttr(value || '') + '">' +
            '<div class="input-group-append"><button type="button" class="btn btn-outline-secondary btn-xoa-rieng-ngay">&times;</button></div>' +
            '</div>'
        );
    }

    function reindexNghiRieng() {
        $('#nghi-rieng-body .nghi-rieng-row').each(function (idx) {
            var $tr = $(this);
            $tr.find('[name^="nghi_rieng["]').each(function () {
                var name = $(this).attr('name') || '';
                if (name.indexOf('[ngay][]') !== -1) {
                    $(this).attr('name', 'nghi_rieng[' + idx + '][ngay][]');
                } else {
                    var field = name.split('][').pop().replace(']', '');
                    $(this).attr('name', 'nghi_rieng[' + idx + '][' + field + ']');
                }
            });
        });
    }

    function addNghiRiengRow(data) {
        data = data || {};
        var idx = $('#nghi-rieng-body .nghi-rieng-row').length;
        var $tr = $('<tr class="nghi-rieng-row"></tr>');
        $tr.append('<td><input type="number" min="1" class="form-control form-control-sm" name="nghi_rieng[' + idx + '][cap_stt]" value="' + (data.cap_stt || '') + '"></td>');
        $tr.append('<td><input type="text" class="form-control form-control-sm" name="nghi_rieng[' + idx + '][bien_so]" list="datalist-bien-so" value="' + escapeAttr(data.bien_so || '') + '"></td>');
        $tr.append('<td><div class="nghi-rieng-ngay-list"></div><button type="button" class="btn btn-sm btn-outline-secondary btn-them-rieng-ngay">+ Ngày</button></td>');
        $tr.append('<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-rieng">&times;</button></td>');
        $('#nghi-rieng-body').append($tr);
        var ngayList = Array.isArray(data.ngay) ? data.ngay : [];
        var $list = $tr.find('.nghi-rieng-ngay-list');
        if (ngayList.length === 0) {
            addNghiRiengNgayRow($list, idx, '');
        } else {
            ngayList.forEach(function (d) {
                addNghiRiengNgayRow($list, idx, d);
            });
        }
    }

    function applyChuongTrinh(rows) {
        var $body = $('#chuong-trinh-body');
        $body.empty();
        rows.forEach(function (row, idx) {
            var $tr = $('<tr></tr>');
            $tr.append('<td><input type="hidden" name="chuong_trinh[' + idx + '][ma]" value="' + escapeAttr(row.ma) + '"><code class="small">' + escapeHtml(row.ma) + '</code></td>');
            $tr.append('<td><input type="text" class="form-control form-control-sm" name="chuong_trinh[' + idx + '][ten]" value="' + escapeAttr(row.ten || '') + '"></td>');
            $tr.append('<td><input type="number" step="0.5" min="0" class="form-control form-control-sm" name="chuong_trinh[' + idx + '][gio]" value="' + (row.gio != null ? row.gio : 0) + '"></td>');
            $tr.append('<td><input type="number" min="0" class="form-control form-control-sm" name="chuong_trinh[' + idx + '][thu_tu]" value="' + (row.thu_tu != null ? row.thu_tu : 0) + '"></td>');
            var checked = row.tinh_dat ? ' checked' : '';
            $tr.append('<td class="text-center"><input type="checkbox" name="chuong_trinh[' + idx + '][tinh_dat]" value="1"' + checked + '></td>');
            $tr.append('<td><input type="hidden" name="chuong_trinh[' + idx + '][mau]" value="' + escapeAttr(row.mau || row.ma) + '"></td>');
            $body.append($tr);
        });
    }

    $(function () {
        $('#btn-them-cap').on('click', function () {
            addCapRow({});
            reindexCapXe();
        });

        $(document).on('click', '.btn-xoa-cap', function () {
            $(this).closest('tr').remove();
            reindexCapXe();
        });

        $('#btn-nghi-auto-thu-nam').on('click', fillNghiCoDinhThuNam);
        $('#btn-them-nghi-co-dinh').on('click', function () {
            addNghiNgayRow('nghi-co-dinh-list');
        });
        $(document).on('click', '.btn-xoa-ngay', function () {
            $(this).closest('.nghi-ngay-row').remove();
        });

        $('#btn-them-nghi-rieng').on('click', function () {
            addNghiRiengRow({});
        });
        $(document).on('click', '.btn-xoa-rieng', function () {
            $(this).closest('tr').remove();
            reindexNghiRieng();
        });
        $(document).on('click', '.btn-them-rieng-ngay', function () {
            var $tr = $(this).closest('tr.nghi-rieng-row');
            reindexNghiRieng();
            var idx = $('#nghi-rieng-body .nghi-rieng-row').index($tr);
            addNghiRiengNgayRow($tr.find('.nghi-rieng-ngay-list'), idx, '');
            reindexNghiRieng();
        });
        $(document).on('click', '.btn-xoa-rieng-ngay', function () {
            var $tr = $(this).closest('tr.nghi-rieng-row');
            var $list = $(this).closest('.nghi-rieng-ngay-list');
            $(this).closest('.input-group').remove();
            if ($list.children().length === 0) {
                reindexNghiRieng();
                var idx = $('#nghi-rieng-body .nghi-rieng-row').index($tr);
                addNghiRiengNgayRow($list, idx, '');
                reindexNghiRieng();
            }
        });

        $('#btn-chuong-trinh-mac-dinh').on('click', function () {
            var hang = $('select[name="hang_dao_tao"]').val() || 'B';
            var rows = (hang === 'B.01' || hang === 'B01') ? (cfg.chuongTrinhMacDinhB01 || []) : (cfg.chuongTrinhMacDinhB || []);
            if (window.confirm('Khôi phục chương trình mặc định hạng ' + hang + '?')) {
                applyChuongTrinh(rows);
            }
        });

        $('#cap-xe-body tr').each(function () {
            bindCapRow($(this));
        });

        var $hang = $('#js-hang-dao-tao');
        if ($hang.length && cfg.hangReloadUrl) {
            $hang.on('change', function () {
                var next = $(this).val();
                var initial = $(this).data('initial-hang');
                if (next === initial) {
                    return;
                }
                var params = { hang_dao_tao: next };
                ['ma_khoa', 'ngay_khai_giang', 'ngay_ket_thuc_du_kien', 'he_so_quy_doi', 'gio_day_moi_ngay', 'so_hoc_vien_mac_dinh'].forEach(function (field) {
                    var $el = $('[name="' + field + '"]');
                    if ($el.length && $el.val()) {
                        params[field] = $el.val();
                    }
                });
                window.location = cfg.hangReloadUrl + '?' + $.param(params);
            });
        }
    });
})(jQuery);
