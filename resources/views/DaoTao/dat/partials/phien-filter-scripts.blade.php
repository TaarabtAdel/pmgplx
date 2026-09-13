<script>
    $('#filter_ma_hoc_vien').select2({
        theme: 'bootstrap4',
        placeholder: 'Nhập mã, tên HV hoặc khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_hoc_vien').closest('.form-group'),
        minimumInputLength: 1,
        ajax: {
            url: @json(route('daotao.pdt.dat.quan-ly-phien.hoc-vien-options')),
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;

                return {
                    results: data.results || [],
                    pagination: {
                        more: !!(data.pagination && data.pagination.more)
                    }
                };
            },
            cache: true
        },
        language: {
            inputTooShort: function () { return 'Nhập ít nhất 1 ký tự để tìm'; },
            searching: function () { return 'Đang tìm...'; },
            noResults: function () { return 'Không tìm thấy học viên'; },
            loadingMore: function () { return 'Đang tải thêm...'; }
        }
    });
    $('#filter_ma_khoa_hoc').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_khoa_hoc').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    });
    $('#filter_ma_giao_vien').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_giao_vien').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; }
        }
    });
</script>
