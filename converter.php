<?php

if (!defined('ABSPATH')) exit;

/**
 * Convert to WebP
 */
function simgx_convert_to_webp($file, $quality = 85, $delete_original = false) {
    if (empty($file) || !file_exists($file)) return false;

    $info = pathinfo($file);
    $ext = strtolower($info['extension']);

    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

    $new_file = $info['dirname'] . '/' . $info['filename'] . '.webp';

    // Load image
    if ($ext === 'png') {
        $img = imagecreatefrompng($file);
    } else {
        $img = imagecreatefromjpeg($file);
    }

    if (!$img) return false;

    // Convert to webp
    imagewebp($img, $new_file, $quality);
    imagedestroy($img);

    // Option delete original
    if ($delete_original) {
        unlink($file);
    }

    return $new_file;
}

/**
 * Convert all old images
 */
function simgx_convert_old_images() {
    global $wpdb;

    $attachments = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'attachment'
        AND post_mime_type IN ('image/jpeg','image/png')
    ");

    $settings = get_option('simgx_settings');
    $quality  = isset($settings['quality']) ? intval($settings['quality']) : 85;
    $delete_original = !empty($settings['delete_original']);

    foreach ($attachments as $id) {
        $file = get_attached_file($id);
        $new_file = simgx_convert_to_webp($file, $quality, $delete_original);

        if ($new_file) {
            update_attached_file($id, $new_file);
        }
    }

    return true;
}
