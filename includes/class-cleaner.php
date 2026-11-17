<?php
/**
 * Simple Imagix Media Cleaner Engine
 *
 * @package SimpleImagix
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIMGX_Cleaner {

    /**
     * Cache used attachment IDs to avoid redundant queries during scan
     *
     * @var array|null
     */
    private static $used_ids_cache = null;

    /**
     * Preload all attachment IDs known to be in use (thumbnails, galleries, site icons)
     *
     * @return array Array of attachment IDs that are definitely in use
     */
    public static function preload_used_ids() {
        if (self::$used_ids_cache !== null) {
            return self::$used_ids_cache;
        }

        global $wpdb;
        $used_ids = [];

        // 1. Featured images (_thumbnail_id)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $thumb_ids = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_thumbnail_id' 
            AND meta_value != ''
        ");
        if (!empty($thumb_ids)) {
            foreach ($thumb_ids as $tid) {
                $used_ids[(int) $tid] = true;
            }
        }

        // 2. WooCommerce product galleries
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $gallery_metas = $wpdb->get_col("
            SELECT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_product_image_gallery' 
            AND meta_value != ''
        ");
        if (!empty($gallery_metas)) {
            foreach ($gallery_metas as $gal) {
                $ids = explode(',', $gal);
                foreach ($ids as $gid) {
                    $used_ids[(int) trim($gid)] = true;
                }
            }
        }

        // 3. Site logo and site icon
        $site_icon = (int) get_option('site_icon');
        if ($site_icon > 0) {
            $used_ids[$site_icon] = true;
        }

        $custom_logo = (int) get_theme_mod('custom_logo');
        if ($custom_logo > 0) {
            $used_ids[$custom_logo] = true;
        }

        self::$used_ids_cache = $used_ids;
        return self::$used_ids_cache;
    }

    /**
     * Check whether an attachment ID is in use anywhere on the site
     *
     * @param int $attachment_id
     * @return bool True if used, false if unused
     */
    public static function is_attachment_used($attachment_id) {
        $used_set = self::preload_used_ids();
        if (isset($used_set[$attachment_id])) {
            return true;
        }

        global $wpdb;

        $file_path = get_attached_file($attachment_id);
        $filename  = $file_path ? pathinfo($file_path, PATHINFO_FILENAME) : '';
        $basename  = $file_path ? basename($file_path) : '';

        // 1. Check in post_content (Gutenberg block attribute, class wp-image-ID, or filename)
        $id_pattern   = '%' . $wpdb->esc_like('"id":' . $attachment_id) . '%';
        $class_pattern = '%' . $wpdb->esc_like('wp-image-' . $attachment_id) . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $content_match = $wpdb->get_var($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type NOT IN ('attachment', 'revision')
            AND post_status NOT IN ('trash', 'auto-draft')
            AND (post_content LIKE %s OR post_content LIKE %s)
            LIMIT 1
        ", $id_pattern, $class_pattern));

        if ($content_match) {
            return true;
        }

        // Also check filename in content if available
        if (!empty($basename)) {
            $file_pattern = '%' . $wpdb->esc_like($basename) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $file_match = $wpdb->get_var($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type NOT IN ('attachment', 'revision')
                AND post_status NOT IN ('trash', 'auto-draft')
                AND post_content LIKE %s
                LIMIT 1
            ", $file_pattern));

            if ($file_match) {
                return true;
            }
        }

        // 2. Check Elementor metadata if elementor is used
        $elem_id_pattern = '%' . $wpdb->esc_like('"id":' . $attachment_id) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $elementor_match = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_elementor_data'
            AND meta_value LIKE %s
            LIMIT 1
        ", $elem_id_pattern));

        if ($elementor_match) {
            return true;
        }

        return false;
    }

    /**
     * Get a batch of image attachments to scan
     *
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function get_image_attachments_batch($offset = 0, $limit = 50) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_date 
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
            AND post_status != 'trash'
            ORDER BY ID DESC
            LIMIT %d OFFSET %d
        ", $limit, $offset));

        return is_array($results) ? $results : [];
    }

    /**
     * Get total count of image attachments
     *
     * @return int
     */
    public static function get_total_image_count() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var("
            SELECT COUNT(ID) 
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
            AND post_status != 'trash'
        ");
    }

    /**
     * Safely delete or trash an attachment
     *
     * @param int $attachment_id
     * @param string $action 'trash' or 'permanent'
     * @return bool
     */
    public static function remove_attachment($attachment_id, $action = 'trash') {
        if (!current_user_can('delete_post', $attachment_id)) {
            return false;
        }

        if ($action === 'permanent') {
            return (bool) wp_delete_attachment($attachment_id, true);
        }

        // Soft trash mode:
        // If MEDIA_TRASH is supported in wp-config, wp_delete_attachment with false puts in trash.
        // Otherwise update post_status to 'trash' so file is preserved on disk until admin confirms deletion.
        if (defined('MEDIA_TRASH') && MEDIA_TRASH) {
            return (bool) wp_delete_attachment($attachment_id, false);
        }

        $updated = wp_update_post([
            'ID'          => $attachment_id,
            'post_status' => 'trash',
        ]);

        return !is_wp_error($updated) && $updated > 0;
    }
}

