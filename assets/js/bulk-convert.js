jQuery(document).ready(function ($) {
    'use strict';

    let totalImages = 0;
    let convertedImages = 0;
    let failedImages = 0;
    let queue = [];
    let isRunning = false;
    const batchSize = 5;

    const $btnStart = $('#simgx-start-bulk');
    const $btnStop = $('#simgx-stop-bulk');
    const $progressContainer = $('#simgx-bulk-progress-container');
    const $progressBar = $('#simgx-bulk-progress-bar');
    const $progressText = $('#simgx-bulk-progress-text');
    const $statusLog = $('#simgx-bulk-log');

    function log(msg) {
        $statusLog.append('<p>' + msg + '</p>');
        $statusLog.scrollTop($statusLog[0].scrollHeight);
    }

    function updateProgress() {
        const percent = totalImages > 0 ? Math.round((convertedImages / totalImages) * 100) : 0;
        $progressBar.css('width', percent + '%');
        $progressText.text(percent + '% (' + convertedImages + ' / ' + totalImages + ')');
    }

    $btnStart.on('click', function (e) {
        e.preventDefault();

        $btnStart.prop('disabled', true);
        $btnStop.show();
        $progressContainer.show();
        $statusLog.empty().show();
        log('Memulai proses pemindaian gambar yang belum dikonversi...');

        isRunning = true;
        convertedImages = 0;
        failedImages = 0;
        updateProgress();

        // Step 1: Ambil semua ID gambar yang belum di-convert
        $.post(simgxData.ajax_url, {
            action: 'simgx_bulk_get_ids',
            nonce: simgxData.nonce
        }, function (res) {
            if (!res.success) {
                log('<strong style="color:red">Error: ' + (res.data ? res.data.message : 'Gagal mengambil data.') + '</strong>');
                resetUI();
                return;
            }

            queue = res.data.ids || [];
            totalImages = res.data.total || 0;

            if (totalImages === 0) {
                log('<strong>Semua gambar sudah dalam format WebP! Tidak ada yang perlu dikonversi.</strong>');
                resetUI();
                return;
            }

            log('Ditemukan <strong>' + totalImages + '</strong> gambar JPEG/PNG. Memproses antrean...');
            processNextBatch();
        }).fail(function () {
            log('<strong style="color:red">Gagal terhubung ke server saat inisialisasi antrean.</strong>');
            resetUI();
        });
    });

    $btnStop.on('click', function (e) {
        e.preventDefault();
        isRunning = false;
        log('<em>Proses dihentikan oleh pengguna.</em>');
        resetUI();
    });

    function resetUI() {
        isRunning = false;
        $btnStart.prop('disabled', false);
        $btnStop.hide();
    }

    function processNextBatch() {
        if (!isRunning) return;

        if (queue.length === 0) {
            log('<strong style="color:green">Konversi selesai! Total berhasil: ' + (convertedImages - failedImages) + ', gagal: ' + failedImages + '.</strong>');
            resetUI();
            return;
        }

        const currentBatch = queue.splice(0, batchSize);

        $.post(simgxData.ajax_url, {
            action: 'simgx_bulk_convert_batch',
            ids: currentBatch,
            nonce: simgxData.nonce
        }, function (res) {
            if (res.success) {
                convertedImages += (res.data.success_count + res.data.failed_count);
                failedImages += res.data.failed_count;
                log('Batch diproses: ' + res.data.success_count + ' berhasil' + (res.data.failed_count > 0 ? ', ' + res.data.failed_count + ' gagal.' : '.'));
            } else {
                convertedImages += currentBatch.length;
                failedImages += currentBatch.length;
                log('<span style="color:red">Gagal memproses batch (' + currentBatch.join(',') + ')</span>');
            }

            updateProgress();

            if (isRunning) {
                processNextBatch();
            }
        }).fail(function () {
            convertedImages += currentBatch.length;
            failedImages += currentBatch.length;
            log('<span style="color:red">Gagal koneksi pada batch gambar. Melanjutkan batch berikutnya...</span>');
            updateProgress();

            if (isRunning) {
                processNextBatch();
            }
        });
    }
});

