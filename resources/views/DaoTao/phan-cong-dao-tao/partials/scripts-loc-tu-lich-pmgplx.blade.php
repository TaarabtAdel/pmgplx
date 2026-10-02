<script>
    $('#filter_ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_kh').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy khóa học'; } }
    });

    $('#filter_ma_gv').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_gv').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy giáo viên'; } }
    });

    $('#filter_bien_so_xe').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm biển số xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_bien_so_xe').closest('.form-group'),
        language: { noResults: function () { return 'Không tìm thấy biển số'; } }
    });
</script>
