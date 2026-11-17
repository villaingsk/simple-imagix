=== Simple Imagix ===
Contributors: Kref Studio
Tags: webp, converter, optimization, image, compression, media cleaner, clean unused images
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.2.1

Convert JPG/PNG to WebP (full + subsizes/thumbnails), AJAX bulk conversion, and unused media cleaner.

== Description ==

Simple Imagix is a lightweight yet comprehensive plugin designed to modernize and clean your WordPress media library. It automatically handles image optimization by converting uploads and thumbnails to the modern WebP format, provides an AJAX bulk converter that prevents timeout issues on all server stacks, and includes a smart scanner to detect and remove unused images.

**Requirements:**
*   **PHP GD Library**: Must be enabled with WebP support on your server (standard on most hosting and panels like aaPanel and HestiaCP).

**Key Features:**

*   **Automatic WebP Conversion**: Converts both the original image and all generated thumbnails/subsizes (`thumbnail`, `medium`, `large`, etc.) to WebP upon upload (can be toggled on/off in settings).
*   **Proper MIME Type Syncing**: Updates `post_mime_type` in the database to `image/webp` for seamless WordPress media handling.
*   **AJAX Batch Bulk Converter**: Converts existing images in batches (5 per batch) with a visual progress bar and real-time status log, preventing 504 timeouts on Apache, Nginx, LiteSpeed, aaPanel, and HestiaCP.
*   **Unused Media Cleaner**: Intelligently scans your media library to detect images not used in Featured Images, post/page/CPT content, WooCommerce product galleries, or page builders (Elementor), with a safe "Move to Trash" option or permanent deletion.
*   **Compression Quality Control**: Fully adjustable quality setting (10-100) allowing you to find the perfect balance between image sharpness and file size.
*   **Space Saving Mode**: Optional setting to automatically delete original JPG/PNG files after conversion.
*   **Clean & Modular Architecture**: Easy-to-use tabbed admin dashboard under **Settings > Simple Imagix**.

== Installation ==

1. Upload the `simple-imagix` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Simple Imagix** to configure quality, run bulk conversions, or scan for unused images.

== Frequently Asked Questions ==

= Does this delete my original images? =
By default, no. However, you can enable "Delete Original Image" in Settings if you wish to remove original JPG/PNG files to save hosting disk space.

= Does it convert WordPress thumbnails/subsizes? =
Yes! Starting from v1.2.0, all generated image sizes (thumbnail, medium, large, etc.) are converted to WebP alongside the main image.

= Does it work on Nginx / aaPanel / HestiaCP? =
Yes! Simple Imagix works entirely at the application level (PHP) and uses an AJAX batching engine, requiring no special `.htaccess` or Nginx rewrite rules.

== Changelog ==

= 1.2.1 - 2026-11-17 =
* Added toggle option to enable/disable automatic WebP conversion on upload.
* Fixed left alignment and layout symmetry in admin tabs and form table.
* Implemented seamless instant tab switching via JavaScript without full page reloads.

= 1.2.0 - 2026-11-17 =
* Added automatic conversion for all thumbnail/subsize variations to WebP.
* Updated `post_mime_type` in database to `image/webp`.
* Replaced synchronous bulk conversion with a robust AJAX batching queue and progress bar.
* Added Unused Media Cleaner with smart usage detection and safe Move to Trash / Permanent Delete actions.
* Redesigned tabbed admin interface under Settings > Simple Imagix.

= 1.1 - 2026-11-16 =
* Initial release with automatic WebP conversion on upload and basic bulk conversion.
