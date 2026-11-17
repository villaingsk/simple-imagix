<?php
/**
 * Simple Imagix Admin Settings & Dashboard
 *
 * @package SimpleImagix
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'simgx_admin_menu');
add_action('admin_enqueue_scripts', 'simgx_admin_assets');

function simgx_admin_menu() {
    add_options_page(
        'Simple Imagix',
        'Simple Imagix',
        'manage_options',
        'simple-imagix',
        'simgx_render_admin_page'
    );
}

function simgx_admin_assets($hook) {
    if ($hook !== 'settings_page_simple-imagix') {
        return;
    }

    wp_enqueue_style(
        'simgx-admin-css',
        SIMGX_URL . 'assets/css/admin.css',
        [],
        '1.2.1'
    );

    wp_enqueue_script(
        'simgx-admin-tabs',
        SIMGX_URL . 'assets/js/admin-tabs.js',
        ['jquery'],
        '1.2.1',
        true
    );

    wp_enqueue_script(
        'simgx-bulk-convert',
        SIMGX_URL . 'assets/js/bulk-convert.js',
        ['jquery', 'simgx-admin-tabs'],
        '1.2.1',
        true
    );

    wp_enqueue_script(
        'simgx-media-cleaner',
        SIMGX_URL . 'assets/js/media-cleaner.js',
        ['jquery', 'simgx-admin-tabs'],
        '1.2.1',
        true
    );

    wp_localize_script('simgx-admin-tabs', 'simgxData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('simgx_admin_nonce'),
    ]);
}

function simgx_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save Settings
    if (isset($_POST['simgx_save_settings']) && check_admin_referer('simgx_settings_action', 'simgx_settings_nonce')) {
        $auto_convert = !empty($_POST['auto_convert']) ? 1 : 0;
        $quality      = isset($_POST['quality']) ? max(10, min(100, intval($_POST['quality']))) : 85;
        $delete_orig  = !empty($_POST['delete_original']) ? 1 : 0;

        update_option('simgx_settings', [
            'auto_convert'    => $auto_convert,
            'quality'         => $quality,
            'delete_original' => $delete_orig,
        ]);

        echo '<div class="updated notice is-dismissible"><p>Pengaturan berhasil disimpan.</p></div>';
    }

    $settings     = get_option('simgx_settings', []);
    $auto_convert = !isset($settings['auto_convert']) || !empty($settings['auto_convert']);
    $quality      = isset($settings['quality']) ? intval($settings['quality']) : 85;
    $delete_orig  = !empty($settings['delete_original']);
    ?>

    <div class="wrap simgx-wrap">
        <h1>Simple Imagix - Image Optimizer & Media Cleaner</h1>
        <p class="description">Optimasi gambar WordPress ke WebP (Full & Subsizes) serta pembersih berkas media tidak terpakai.</p>

        <h2 class="nav-tab-wrapper simgx-nav-tab-wrapper">
            <a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">Pengaturan Dasar</a>
            <a href="#bulk" class="nav-tab" data-tab="bulk">Bulk Convert (WebP)</a>
            <a href="#cleaner" class="nav-tab" data-tab="cleaner">Unused Media Cleaner</a>
        </h2>

        <!-- TAB 1: PENGATURAN DASAR -->
        <div id="tab-settings" class="simgx-tab-content active">
            <h2>Pengaturan Konversi WebP Otomatis</h2>
            <form method="post" action="">
                <?php wp_nonce_field('simgx_settings_action', 'simgx_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Konversi Otomatis saat Upload</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_convert" value="1" <?php checked($auto_convert); ?>>
                                Otomatis konversi gambar JPG dan PNG ke WebP saat di-upload ke Media Library.
                            </label>
                            <p class="description">Jika dinonaktifkan, upload gambar baru akan disimpan apa adanya tanpa dikonversi ke WebP.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="simgx_quality">Kualitas Kompresi (10 - 100)</label></th>
                        <td>
                            <input type="number" id="simgx_quality" name="quality" value="<?php echo esc_attr($quality); ?>" min="10" max="100" class="small-text">
                            <p class="description">Rekomendasi: 80 - 85 untuk kualitas tajam dengan ukuran file hemat.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Hapus File Asli (JPG/PNG)?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="delete_original" value="1" <?php checked($delete_orig); ?>>
                                Hapus file JPG/PNG asli setelah berhasil dikonversi ke WebP (Menghemat ruang disk hosting).
                            </label>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" name="simgx_save_settings" class="button button-primary">Simpan Pengaturan</button>
                </p>
            </form>
        </div>

        <!-- TAB 2: BULK CONVERT -->
        <div id="tab-bulk" class="simgx-tab-content">
            <h2>Konversi Massal Gambar Lama ke WebP</h2>
            <p>Fitur ini menggunakan sistem antrean AJAX (5 gambar per batch) sehingga aman dari batas <em>timeout</em> server di Apache, Nginx, aaPanel, maupun HestiaCP.</p>

            <div style="margin: 20px 0;">
                <button type="button" id="simgx-start-bulk" class="button button-primary">Mulai Konversi Massal</button>
                <button type="button" id="simgx-stop-bulk" class="button button-secondary" style="display:none; margin-left: 8px;">Hentikan</button>
            </div>

            <div id="simgx-bulk-progress-container" class="simgx-progress-container" style="display:none;">
                <div id="simgx-bulk-progress-bar" class="simgx-progress-bar"></div>
                <div id="simgx-bulk-progress-text" class="simgx-progress-text">0%</div>
            </div>

            <div id="simgx-bulk-log" class="simgx-status-log" style="display:none;"></div>
        </div>

        <!-- TAB 3: MEDIA CLEANER -->
        <div id="tab-cleaner" class="simgx-tab-content">
            <h2>Pembersih Media yang Tidak Terpakai (Unused Media)</h2>
            <p>Memindai berkas gambar di Media Library yang tidak lagi digunakan sebagai <em>Featured Image</em>, di dalam konten (Post/Page/CPT), WooCommerce Gallery, maupun Page Builder.</p>

            <div style="margin: 20px 0;">
                <button type="button" id="simgx-start-scan" class="button button-primary">Pindai Media Sekarang</button>
            </div>

            <div id="simgx-scan-progress-container" class="simgx-progress-container" style="display:none;">
                <div id="simgx-scan-progress-bar" class="simgx-progress-bar"></div>
                <div id="simgx-scan-progress-text" class="simgx-progress-text">0%</div>
            </div>

            <div id="simgx-cleaner-results" style="display:none;">
                <div class="simgx-cleaner-actions">
                    <div>
                        <strong>Ditemukan: <span id="simgx-unused-count" class="simgx-badge simgx-badge-warning">0</span> gambar tidak terpakai</strong>
                    </div>
                    <div>
                        <button type="button" id="simgx-action-trash" class="button button-secondary">Pindahkan Terpilih ke Trash</button>
                        <button type="button" id="simgx-action-delete" class="button button-link-delete simgx-button-danger" style="margin-left: 8px;">Hapus Permanen</button>
                    </div>
                </div>

                <table class="simgx-cleaner-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;"><input type="checkbox" id="simgx-select-all"></th>
                            <th style="width: 70px;">Pratinjau</th>
                            <th>Judul & Nama Berkas</th>
                            <th style="width: 120px;">Ukuran Berkas</th>
                            <th style="width: 130px;">Tanggal Unggah</th>
                        </tr>
                    </thead>
                    <tbody id="simgx-unused-table-body">
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php
}
