<?php
/**
 * Plugin Name: Simple Imagix
 * Plugin URI: https://github.com/justyupi/simple-imagix
 * Description: Convert JPG/PNG to WebP + compression + resize + convert old images.
 * Version: 1.0
 * Author: Kref Studio
 * Author URI: https://krefstudio.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.9
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Text Domain: simple-imagix
 */

if (!defined('ABSPATH')) exit;

define('SIMGX_PATH', plugin_dir_path(__FILE__));
define('SIMGX_URL', plugin_dir_url(__FILE__));

require_once SIMGX_PATH . 'converter.php';
require_once SIMGX_PATH . 'admin-page.php';

/**
 * Convert image after upload
 */
add_filter('wp_generate_attachment_metadata', 'simgx_convert_on_upload', 10, 2);

function simgx_convert_on_upload($metadata, $attachment_id) {
    $file = get_attached_file($attachment_id);
    $mime = get_post_mime_type($attachment_id);

    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        return $metadata;
    }

    $settings = get_option('simgx_settings');
    $quality  = isset($settings['quality']) ? intval($settings['quality']) : 85;
    $delete_original = !empty($settings['delete_original']);

    $new_file = simgx_convert_to_webp($file, $quality, $delete_original);

    if ($new_file) {
        update_attached_file($attachment_id, $new_file);
    }

    return $metadata;
}

