<script>
(function () {
    $(document).on('submit', '[id^="formXuatExcelHv"]', function () {
        if ($(this).find('input.hv-export-col:checked').length === 0) {
            alert('Chọn ít nhất một cột để xuất.');
            return false;
        }
    });
    $(document).on('click', '.hv-export-chon-uu-tien', function () {
        const $form = $($(this).data('form'));
        $form.find('.hv-export-col').prop('checked', false);
        $form.find('.hv-export-col[data-mac-dinh="1"]').prop('checked', true);
    });
    $(document).on('click', '.hv-export-chon-tat', function () {
        $($(this).data('form')).find('.hv-export-col').prop('checked', true);
    });
    $(document).on('click', '.hv-export-bo-chon', function () {
        $($(this).data('form')).find('.hv-export-col').prop('checked', false);
    });
})();
</script>
