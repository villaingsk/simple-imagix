<?php
/**
 * Simple Imagix Converter Engine
 *
 * @package SimpleImagix
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIMGX_Converter {

    /**
     * Convert an image file on disk to WebP
     *
     * @param string $file Absolute path to source image
     * @param int $quality Compression quality (1-100)
     * @param bool $delete_original Whether to delete the original file
     * @return string|false Path to the new WebP file or false on failure
     */
    public static function convert_file_to_webp($file, $quality = 85, $delete_original = false) {
        if (!function_exists('imagewebp')) {
            return false;
        }

        if (empty($file) || !file_exists($file)) {
            return false;
        }

        $info = pathinfo($file);
        $ext = isset($info['extension']) ? strtolower($info['extension']) : '';

        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return false;
        }

        // Target path
        $new_file = $info['dirname'] . '/' . $info['filename'] . '.webp';

        // Load image using GD
        $img = false;
        if ($ext === 'png') {
            $img = @imagecreatefrompng($file);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }
        } else {
            $img = @imagecreatefromjpeg($file);
        }

        if (!$img) {
            return false;
        }

        // Convert to WebP
        $converted = @imagewebp($img, $new_file, $quality);
        imagedestroy($img);

        if (!$converted || !file_exists($new_file)) {
            return false;
        }

        // Remove original file if requested
        if ($delete_original && $file !== $new_file) {
            @unlink($file);
        }

        return $new_file;
    }

    /**
     * Process an entire attachment: main file, generated subsizes (thumbnails),
     * metadata, and post_mime_type in database.
     *
     * @param int $attachment_id
     * @param int|null $quality
     * @param bool|null $delete_original
     * @return bool True if successfully converted, false otherwise
     */
    public static function process_attachment($attachment_id, $quality = null, $delete_original = null) {
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }

        $settings = get_option('simgx_settings', []);
        if ($quality === null) {
            $quality = isset($settings['quality']) ? intval($settings['quality']) : 85;
        }
        if ($delete_original === null) {
            $delete_original = !empty($settings['delete_original']);
        }

        $main_file = get_attached_file($attachment_id);
        if (!$main_file || !file_exists($main_file)) {
            return false;
        }

        $mime = get_post_mime_type($attachment_id);
        $ext = strtolower(pathinfo($main_file, PATHINFO_EXTENSION));

        // Skip if already webp and not jpeg/png
        if ($mime === 'image/webp' && $ext === 'webp') {
            return true;
        }

        if (!in_array($mime, ['image/jpeg', 'image/png'], true) && !in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return false;
        }

        $upload_dir = dirname($main_file);

        // 1. Convert main image
        $new_main_file = self::convert_file_to_webp($main_file, $quality, $delete_original);
        if (!$new_main_file) {
            return false;
        }

        update_attached_file($attachment_id, $new_main_file);

        // 2. Convert subsizes / thumbnails in metadata
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (is_array($metadata)) {
            // Update relative path to main file if stored
            if (!empty($metadata['file'])) {
                $info = pathinfo($metadata['file']);
                $metadata['file'] = ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '') . $info['filename'] . '.webp';
            }

            if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size_name => $size_data) {
                    if (empty($size_data['file'])) {
                        continue;
                    }

                    $subsize_path = $upload_dir . '/' . $size_data['file'];
                    if (file_exists($subsize_path)) {
                        $new_subsize = self::convert_file_to_webp($subsize_path, $quality, $delete_original);
                        if ($new_subsize) {
                            $metadata['sizes'][$size_name]['file']      = basename($new_subsize);
                            $metadata['sizes'][$size_name]['mime-type'] = 'image/webp';
                        }
                    }
                }
            }

            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        // 3. Update database post_mime_type to image/webp
        wp_update_post([
            'ID'             => $attachment_id,
            'post_mime_type' => 'image/webp',
        ]);

        return true;
    }

    /**
     * Handle conversion upon upload via wp_generate_attachment_metadata filter
     *
     * @param array $metadata
     * @param int $attachment_id
     * @return array
     */
    public static function handle_upload_filter($metadata, $attachment_id) {
        $settings = get_option('simgx_settings', []);
        $auto_convert = !isset($settings['auto_convert']) || !empty($settings['auto_convert']);

        // Skip automatic conversion if disabled in settings
        if (!$auto_convert) {
            return $metadata;
        }

        $file = get_attached_file($attachment_id);
        $mime = get_post_mime_type($attachment_id);

        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return $metadata;
        }

        $quality     = isset($settings['quality']) ? intval($settings['quality']) : 85;
        $delete_orig = !empty($settings['delete_original']);

        $new_main_file = self::convert_file_to_webp($file, $quality, $delete_orig);
        if ($new_main_file) {
            update_attached_file($attachment_id, $new_main_file);

            // Update database post_mime_type to image/webp
            wp_update_post([
                'ID'             => $attachment_id,
                'post_mime_type' => 'image/webp',
            ]);

            // Update main file and subsizes in metadata array
            if (is_array($metadata)) {
                if (!empty($metadata['file'])) {
                    $info = pathinfo($metadata['file']);
                    $metadata['file'] = ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '') . $info['filename'] . '.webp';
                }

                $upload_dir = dirname($new_main_file);
                if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size_name => $size_data) {
                        if (empty($size_data['file'])) {
                            continue;
                        }

                        $subsize_path = $upload_dir . '/' . $size_data['file'];
                        if (file_exists($subsize_path)) {
                            $new_subsize = self::convert_file_to_webp($subsize_path, $quality, $delete_orig);
                            if ($new_subsize) {
                                $metadata['sizes'][$size_name]['file']      = basename($new_subsize);
                                $metadata['sizes'][$size_name]['mime-type'] = 'image/webp';
                            }
                        }
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Get IDs of all images needing conversion (JPEG/PNG)
     *
     * @return array
     */
    public static function get_unconverted_image_ids() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type IN ('image/jpeg', 'image/png')
            ORDER BY ID DESC
        ");

        return is_array($ids) ? array_map('intval', $ids) : [];
    }
}
