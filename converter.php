<?php
/**
 * Simple Imagix Backward Compatibility Wrapper
 *
 * @package SimpleImagix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert to WebP (Legacy wrapper)
 */
function simgx_convert_to_webp($file, $quality = 85, $delete_original = false) {
    return SIMGX_Converter::convert_file_to_webp($file, $quality, $delete_original);
}

/**
 * Convert all old images (Legacy wrapper)
 */
function simgx_convert_old_images() {
    $ids = SIMGX_Converter::get_unconverted_image_ids();
    foreach ($ids as $id) {
        SIMGX_Converter::process_attachment($id);
    }
    return true;
}
