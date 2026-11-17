jQuery(document).ready(function ($) {
    'use strict';

    let scanOffset = 0;
    const scanLimit = 30;
    let totalToScan = 0;
    let totalScanned = 0;
    let unusedItems = [];
    let isScanning = false;

    const $btnScan = $('#simgx-start-scan');
    const $scanProgressContainer = $('#simgx-scan-progress-container');
    const $scanProgressBar = $('#simgx-scan-progress-bar');
    const $scanProgressText = $('#simgx-scan-progress-text');
    const $resultsContainer = $('#simgx-cleaner-results');
    const $tableBody = $('#simgx-unused-table-body');
    const $unusedCountBadge = $('#simgx-unused-count');
    const $selectAll = $('#simgx-select-all');

    const $btnTrash = $('#simgx-action-trash');
    const $btnDelete = $('#simgx-action-delete');

    function updateScanProgress() {
        const percent = totalToScan > 0 ? Math.round((totalScanned / totalToScan) * 100) : 0;
        $scanProgressBar.css('width', percent + '%');
        $scanProgressText.text('Memindai: ' + percent + '% (' + totalScanned + ' / ' + totalToScan + ')');
    }

    $btnScan.on('click', function (e) {
        e.preventDefault();

        $btnScan.prop('disabled', true);
        $scanProgressContainer.show();
        $resultsContainer.hide();
        $tableBody.empty();
        unusedItems = [];
        scanOffset = 0;
        totalScanned = 0;
        isScanning = true;

        // Step 1: Hitung total media
        $.post(simgxData.ajax_url, {
            action: 'simgx_cleaner_get_total',
            nonce: simgxData.nonce
        }, function (res) {
            if (!res.success) {
                alert('Gagal mengambil data media.');
                $btnScan.prop('disabled', false);
                return;
            }

            totalToScan = res.data.total;
            if (totalToScan === 0) {
                alert('Tidak ada gambar di Media Library.');
                $btnScan.prop('disabled', false);
                $scanProgressContainer.hide();
                return;
            }

            updateScanProgress();
            processScanBatch();
        }).fail(function () {
            alert('Koneksi ke server gagal.');
            $btnScan.prop('disabled', false);
        });
    });

    function processScanBatch() {
        if (!isScanning) return;

        $.post(simgxData.ajax_url, {
            action: 'simgx_cleaner_scan_batch',
            offset: scanOffset,
            limit: scanLimit,
            nonce: simgxData.nonce
        }, function (res) {
            if (res.success) {
                totalScanned += res.data.scanned;
                scanOffset += res.data.scanned;

                if (res.data.unused && res.data.unused.length > 0) {
                    unusedItems = unusedItems.concat(res.data.unused);
                    appendRows(res.data.unused);
                }

                updateScanProgress();

                if (scanOffset < totalToScan && res.data.scanned > 0) {
                    processScanBatch();
                } else {
                    finishScan();
                }
            } else {
                alert('Terjadi kesalahan saat pemindaian.');
                finishScan();
            }
        }).fail(function () {
            alert('Gagal memproses batch scanner.');
            finishScan();
        });
    }

    function appendRows(items) {
        let html = '';
        items.forEach(function (item) {
            html += '<tr id="simgx-row-' + item.id + '">';
            html += '  <td><input type="checkbox" class="simgx-item-cb" value="' + item.id + '"></td>';
            html += '  <td><img src="' + item.thumb + '" class="simgx-thumb-preview" alt=""></td>';
            html += '  <td><strong>' + item.title + '</strong><br><small class="description">' + item.filename + '</small></td>';
            html += '  <td>' + item.size + '</td>';
            html += '  <td>' + item.date + '</td>';
            html += '</tr>';
        });
        $tableBody.append(html);
    }

    function finishScan() {
        isScanning = false;
        $btnScan.prop('disabled', false);
        $scanProgressContainer.hide();
        $resultsContainer.show();
        $unusedCountBadge.text(unusedItems.length);

        if (unusedItems.length === 0) {
            $tableBody.html('<tr><td colspan="5" style="text-align:center; padding: 20px;"><em>Luar biasa! Tidak ada gambar yang menganggur/tidak terpakai.</em></td></tr>');
            $btnTrash.prop('disabled', true);
            $btnDelete.prop('disabled', true);
        } else {
            $btnTrash.prop('disabled', false);
            $btnDelete.prop('disabled', false);
        }
    }

    // Select all checkboxes
    $selectAll.on('change', function () {
        $('.simgx-item-cb').prop('checked', $(this).is(':checked'));
    });

    // Delete or Trash handlers
    function getSelectedIds() {
        const ids = [];
        $('.simgx-item-cb:checked').each(function () {
            ids.push($(this).val());
        });
        return ids;
    }

    function performAction(actionType) {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert('Pilih setidaknya satu gambar untuk dihapus.');
            return;
        }

        const confirmMsg = actionType === 'permanent'
            ? 'PERINGATAN: ' + ids.length + ' gambar terpilih akan DIHAPUS PERMANEN dari server dan database. Tindakan ini tidak bisa dibatalkan!\n\nApakah Anda yakin?'
            : 'Pindahkan ' + ids.length + ' gambar terpilih ke Trash/Tempat Sampah?';

        if (!confirm(confirmMsg)) {
            return;
        }

        $btnTrash.prop('disabled', true);
        $btnDelete.prop('disabled', true);

        $.post(simgxData.ajax_url, {
            action: 'simgx_cleaner_delete_batch',
            ids: ids,
            delete_action: actionType,
            nonce: simgxData.nonce
        }, function (res) {
            $btnTrash.prop('disabled', false);
            $btnDelete.prop('disabled', false);

            if (res.success) {
                alert('Berhasil memproses ' + res.data.deleted + ' gambar.');
                ids.forEach(function (id) {
                    $('#simgx-row-' + id).remove();
                });
                const remaining = $('.simgx-item-cb').length;
                $unusedCountBadge.text(remaining);
                if (remaining === 0) {
                    $tableBody.html('<tr><td colspan="5" style="text-align:center; padding: 20px;"><em>Semua gambar tidak terpakai telah dibersihkan.</em></td></tr>');
                    $selectAll.prop('checked', false);
                }
            } else {
                alert('Gagal: ' + (res.data ? res.data.message : 'Terjadi kesalahan.'));
            }
        }).fail(function () {
            $btnTrash.prop('disabled', false);
            $btnDelete.prop('disabled', false);
            alert('Gagal menghubungi server.');
        });
    }

    $btnTrash.on('click', function (e) {
        e.preventDefault();
        performAction('trash');
    });

    $btnDelete.on('click', function (e) {
        e.preventDefault();
        performAction('permanent');
    });
});

