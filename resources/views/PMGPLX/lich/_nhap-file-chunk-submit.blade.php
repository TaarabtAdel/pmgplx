@once
@push('scripts')
<script>
(function () {
    var CHUNK_SIZE = {{ \App\Http\Controllers\PMGPLX\NhapLichTuFileController::SUBMIT_CHUNK_SIZE }};
    var CHUNK_MIN_ROWS = {{ \App\Http\Controllers\PMGPLX\NhapLichTuFileController::SUBMIT_CHUNK_MIN_ROWS }};

    window.lichNhapFileChunkSubmit = function (options) {
        var $form = options.$form;
        var totalRows = options.totalRows;
        var readRows = options.readRows;
        var cheDoCapNhatSelector = options.cheDoCapNhatSelector || null;
        var progressLabel = options.progressLabel || 'Đang gửi dữ liệu';

        if (totalRows <= CHUNK_MIN_ROWS) {
            return false;
        }

        $form.on('submit.lichChunk', function (e) {
            e.preventDefault();

            $form.find('.lich-datetime-fields').each(function () {
                if (typeof syncLichDatetimeField === 'function') {
                    syncLichDatetimeField($(this));
                }
            });

            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }

            var $btn = $form.find('button[type="submit"]');
            var btnOriginalText = $btn.text();
            var $progress = $('#lichNhapFileChunkProgress');
            if (!$progress.length) {
                $progress = $('<div class="alert alert-info mt-3" id="lichNhapFileChunkProgress"></div>');
                $btn.before($progress);
            }

            function restoreSubmitButton() {
                $btn.prop('disabled', false).text(btnOriginalText);
            }

            $btn.prop('disabled', true).text('Đang xử lý');
            var chunkTotal = Math.ceil(totalRows / CHUNK_SIZE);
            var csrf = $form.find('input[name="_token"]').val();

            function sendChunk(chunkIndex) {
                var start = chunkIndex * CHUNK_SIZE;
                var end = Math.min(start + CHUNK_SIZE, totalRows);
                var rows = readRows(start, end);
                var isLast = chunkIndex >= chunkTotal - 1;

                $progress.text(
                    progressLabel + ': phần ' + (chunkIndex + 1) + '/' + chunkTotal
                    + ' (dòng ' + (start + 1) + '–' + end + ')…'
                );

                var payload = {
                    _token: csrf,
                    rows_json: JSON.stringify(rows),
                    chunk_index: chunkIndex,
                    chunk_total: chunkTotal,
                    chunk_finalize: isLast ? 1 : 0
                };

                if (cheDoCapNhatSelector) {
                    payload.che_do_cap_nhat = $(cheDoCapNhatSelector).is(':checked') ? 1 : 0;
                }

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: payload,
                    dataType: 'json'
                }).done(function (res) {
                    if (!res || !res.ok) {
                        restoreSubmitButton();
                        $progress.removeClass('alert-info').addClass('alert-danger')
                            .text((res && res.message) ? res.message : 'Gửi dữ liệu thất bại.');
                        return;
                    }

                    if (res.redirect) {
                        $progress.text('Hoàn tất — đang chuyển trang…');
                        window.location.href = res.redirect;
                        return;
                    }

                    sendChunk(chunkIndex + 1);
                }).fail(function (xhr) {
                    restoreSubmitButton();
                    var msg = 'Gửi dữ liệu thất bại.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $progress.removeClass('alert-info').addClass('alert-danger').text(msg);
                });
            }

            sendChunk(0);
        });

        return true;
    };
})();
</script>
@endpush
@endonce
