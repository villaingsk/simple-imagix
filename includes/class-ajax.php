<?php
/**
 * Simple Imagix AJAX Handlers
 *
 * @package SimpleImagix
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIMGX_Ajax {

    /**
     * Register all AJAX action hooks
     */
    public static function init() {
        // Bulk Convert
        add_action('wp_ajax_simgx_bulk_get_ids', [__CLASS__, 'bulk_get_ids']);
        add_action('wp_ajax_simgx_bulk_convert_batch', [__CLASS__, 'bulk_convert_batch']);

        // Media Cleaner
        add_action('wp_ajax_simgx_cleaner_get_total', [__CLASS__, 'cleaner_get_total']);
        add_action('wp_ajax_simgx_cleaner_scan_batch', [__CLASS__, 'cleaner_scan_batch']);
        add_action('wp_ajax_simgx_cleaner_delete_batch', [__CLASS__, 'cleaner_delete_batch']);
    }

    /**
     * Check permissions and nonce
     */
    private static function verify_request() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized permissions.'], 403);
        }
        check_ajax_referer('simgx_admin_nonce', 'nonce');
    }

    /**
     * AJAX: Get all unconverted image IDs
     */
    public static function bulk_get_ids() {
        self::verify_request();

        $ids = SIMGX_Converter::get_unconverted_image_ids();
        wp_send_json_success([
            'ids'   => $ids,
            'total' => count($ids),
        ]);
    }

    /**
     * AJAX: Convert a batch of image IDs
     */
    public static function bulk_convert_batch() {
        self::verify_request();

        $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
        if (empty($ids)) {
            wp_send_json_error(['message' => 'No IDs provided.']);
        }

        $settings = get_option('simgx_settings', []);
        $quality  = isset($settings['quality']) ? intval($settings['quality']) : 85;
        $delete_orig = !empty($settings['delete_original']);

        $success = 0;
        $failed  = 0;

        foreach ($ids as $id) {
            if ($id <= 0) continue;
            $res = SIMGX_Converter::process_attachment($id, $quality, $delete_orig);
            if ($res) {
                $success++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            'success_count' => $success,
            'failed_count'  => $failed,
        ]);
    }

    /**
     * AJAX: Get total images count for scanner
     */
    public static function cleaner_get_total() {
        self::verify_request();

        $total = SIMGX_Cleaner::get_total_image_count();
        wp_send_json_success(['total' => $total]);
    }

    /**
     * AJAX: Scan a batch of attachments for unused images
     */
    public static function cleaner_scan_batch() {
        self::verify_request();

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit  = isset($_POST['limit']) ? intval($_POST['limit']) : 30;

        $batch = SIMGX_Cleaner::get_image_attachments_batch($offset, $limit);
        $unused_items = [];

        foreach ($batch as $att) {
            $id = (int) $att->ID;
            $is_used = SIMGX_Cleaner::is_attachment_used($id);

            if (!$is_used) {
                $file = get_attached_file($id);
                $filesize = ($file && file_exists($file)) ? size_format(filesize($file)) : '-';
                $thumb_url = wp_get_attachment_image_url($id, 'thumbnail');
                if (!$thumb_url) {
                    $thumb_url = wp_get_attachment_url($id);
                }

                $unused_items[] = [
                    'id'        => $id,
                    'title'     => esc_html($att->post_title ?: basename($file ?: 'Image #' . $id)),
                    'filename'  => esc_html(basename($file ?: '-')),
                    'thumb'     => esc_url($thumb_url ?: ''),
                    'size'      => esc_html($filesize),
                    'date'      => esc_html(mysql2date('Y-m-d', $att->post_date)),
                ];
            }
        }

        wp_send_json_success([
            'scanned' => count($batch),
            'unused'  => $unused_items,
        ]);
    }

    /**
     * AJAX: Delete or trash selected unused attachments
     */
    public static function cleaner_delete_batch() {
        self::verify_request();

        $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
        $action = isset($_POST['delete_action']) && $_POST['delete_action'] === 'permanent' ? 'permanent' : 'trash';

        if (empty($ids)) {
            wp_send_json_error(['message' => 'No items selected.']);
        }

        $deleted = 0;
        $failed  = 0;

        foreach ($ids as $id) {
            $ok = SIMGX_Cleaner::remove_attachment($id, $action);
            if ($ok) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            'deleted' => $deleted,
            'failed'  => $failed,
            'action'  => $action,
        ]);
    }
}

