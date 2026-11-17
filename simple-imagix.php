<?php
/**
 * Plugin Name: Simple Imagix
 * Description: Convert JPG/PNG to WebP (Full & Subsizes), AJAX bulk conversion, and Unused Media Cleaner.
 * Version: 1.2.1
 * Author: Kref Studio
 * Author URI: https://krefstudio.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Text Domain: simple-imagix
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIMGX_VERSION', '1.2.1');
define('SIMGX_PATH', plugin_dir_path(__FILE__));
define('SIMGX_URL', plugin_dir_url(__FILE__));

// Load includes
require_once SIMGX_PATH . 'includes/class-converter.php';
require_once SIMGX_PATH . 'includes/class-cleaner.php';
require_once SIMGX_PATH . 'includes/class-ajax.php';
require_once SIMGX_PATH . 'converter.php';
require_once SIMGX_PATH . 'admin-page.php';

// Initialize AJAX handlers
SIMGX_Ajax::init();

// Hook into image upload process to convert to WebP (Main image + all subsizes)
add_filter('wp_generate_attachment_metadata', ['SIMGX_Converter', 'handle_upload_filter'], 10, 2);

// Add quick settings link on Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'simgx_add_plugin_action_links');

function simgx_add_plugin_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=simple-imagix')) . '">' . __('Settings', 'simple-imagix') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
