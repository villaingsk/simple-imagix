<?php

if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'simgx_menu');

function simgx_menu() {
    add_options_page(
        'Simple Imagix',
        'Simple Imagix',
        'manage_options',
        'simple-imagix',
        'simgx_settings_page'
    );
}

function simgx_settings_page() {
    if (isset($_POST['save_settings'])) {
        update_option('simgx_settings', [
            'quality'         => intval($_POST['quality']),
            'delete_original' => isset($_POST['delete_original']) ? 1 : 0,
        ]);

        echo '<div class="updated"><p>Settings Saved.</p></div>';
    }

    if (isset($_POST['convert_old'])) {
        simgx_convert_old_images();
        echo '<div class="updated"><p>Semua gambar lama sudah dikonversi ke WebP.</p></div>';
    }

    $settings = get_option('simgx_settings');
    $quality  = isset($settings['quality']) ? $settings['quality'] : 85;
    $delete_original = !empty($settings['delete_original']);
    ?>

    <div class="wrap">
        <h1>Simple Imagix Settings</h1>

        <form method="post">
            <table class="form-table">

                <tr>
                    <th scope="row">Compression Quality</th>
                    <td>
                        <input type="number" name="quality" value="<?php echo esc_attr($quality); ?>" min="1" max="100">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Delete Original Image?</th>
                    <td>
                        <label>
                            <input type="checkbox" name="delete_original" <?php checked($delete_original); ?>>
                            Hapus JPG/PNG setelah convert ke WebP
                        </label>
                    </td>
                </tr>

            </table>

            <p>
                <button class="button button-primary" name="save_settings">Save Settings</button>
            </p>

        </form>

        <hr>

        <h2>Convert Semua Gambar Lama</h2>
        <p>Akan meng-convert seluruh JPG/PNG menjadi WebP.</p>
        <form method="post">
            <button class="button button-secondary" name="convert_old">Convert Sekarang</button>
        </form>
    </div>

    <?php
}
